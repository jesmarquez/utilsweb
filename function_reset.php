<?php
/**
 * This function will empty a course of user data.
 * It will retain the activities and the structure of the course.
 *
 * @param object $data an object containing all the settings including courseid (without magic quotes)
 * @return array status array of array component, item, error
 */
function reset_course_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->libdir.'/completionlib.php');
    require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
    require_once($CFG->dirroot.'/group/lib.php');

    $data->courseid = $data->id;
    $context = context_course::instance($data->courseid);

    $eventparams = array(
        'context' => $context,
        'courseid' => $data->id,
        'other' => array(
            'reset_options' => (array) $data
        )
    );
    $event = \core\event\course_reset_started::create($eventparams);
    $event->trigger();

    // Calculate the time shift of dates.
    if (!empty($data->reset_start_date)) {
        // Time part of course startdate should be zero.
        $data->timeshift = $data->reset_start_date - usergetmidnight($data->reset_start_date_old);
    } else {
        $data->timeshift = 0;
    }

    // Result array: component, item, error.
    $status = array();

    // Start the resetting.
    $componentstr = get_string('general');

    // Move the course start time.
    if (!empty($data->reset_start_date) and $data->timeshift) {
        // Change course start data.
        $DB->set_field('course', 'startdate', $data->reset_start_date, array('id' => $data->courseid));
        // Update all course and group events - do not move activity events.
        $updatesql = "UPDATE {event}
                         SET timestart = timestart + ?
                       WHERE courseid=? AND instance=0";
        $DB->execute($updatesql, array($data->timeshift, $data->courseid));

        // Update any date activity restrictions.
        if ($CFG->enableavailability) {
            \availability_date\condition::update_all_dates($data->courseid, $data->timeshift);
        }

        // Update completion expected dates.
        if ($CFG->enablecompletion) {
            $modinfo = get_fast_modinfo($data->courseid);
            $changed = false;
            foreach ($modinfo->get_cms() as $cm) {
                if ($cm->completion && !empty($cm->completionexpected)) {
                    $DB->set_field('course_modules', 'completionexpected', $cm->completionexpected + $data->timeshift,
                        array('id' => $cm->id));
                    $changed = true;
                }
            }

            // Clear course cache if changes made.
            if ($changed) {
                rebuild_course_cache($data->courseid, true);
            }

            // Update course date completion criteria.
            \completion_criteria_date::update_date($data->courseid, $data->timeshift);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);
    }

    if (!empty($data->reset_end_date)) {
        // If the user set a end date value respect it.
        $DB->set_field('course', 'enddate', $data->reset_end_date, array('id' => $data->courseid));
    } else if ($data->timeshift > 0 && $data->reset_end_date_old) {
        // If there is a time shift apply it to the end date as well.
        $enddate = $data->reset_end_date_old + $data->timeshift;
        $DB->set_field('course', 'enddate', $enddate, array('id' => $data->courseid));
    }

    if (!empty($data->reset_events)) {
        $DB->delete_records('event', array('courseid' => $data->courseid));
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteevents', 'calendar'), 'error' => false);
    }

    if (!empty($data->reset_notes)) {
        require_once($CFG->dirroot.'/notes/lib.php');
        note_delete_all($data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('deletenotes', 'notes'), 'error' => false);
    }

    if (!empty($data->delete_blog_associations)) {
        require_once($CFG->dirroot.'/blog/lib.php');
        blog_remove_associations_for_course($data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteblogassociations', 'blog'), 'error' => false);
    }

    if (!empty($data->reset_completion)) {
        // Delete course and activity completion information.
        $course = $DB->get_record('course', array('id' => $data->courseid));
        $cc = new completion_info($course);
        $cc->delete_all_completion_data();
        $status[] = array('component' => $componentstr,
                'item' => get_string('deletecompletiondata', 'completion'), 'error' => false);
    }

    if (!empty($data->reset_competency_ratings)) {
        \core_competency\api::hook_course_reset_competency_ratings($data->courseid);
        $status[] = array('component' => $componentstr,
            'item' => get_string('deletecompetencyratings', 'core_competency'), 'error' => false);
    }

    $componentstr = get_string('roles');

    if (!empty($data->reset_roles_overrides)) {
        $children = $context->get_child_contexts();
        foreach ($children as $child) {
            $DB->delete_records('role_capabilities', array('contextid' => $child->id));
        }
        $DB->delete_records('role_capabilities', array('contextid' => $context->id));
        // Force refresh for logged in users.
        $context->mark_dirty();
        $status[] = array('component' => $componentstr, 'item' => get_string('deletecourseoverrides', 'role'), 'error' => false);
    }

    if (!empty($data->reset_roles_local)) {
        $children = $context->get_child_contexts();
        foreach ($children as $child) {
            role_unassign_all(array('contextid' => $child->id));
        }
        // Force refresh for logged in users.
        $context->mark_dirty();
        $status[] = array('component' => $componentstr, 'item' => get_string('deletelocalroles', 'role'), 'error' => false);
    }

    // First unenrol users - this cleans some of related user data too, such as forum subscriptions, tracking, etc.
    $data->unenrolled = array();
    if (!empty($data->unenrol_users)) {
        $plugins = enrol_get_plugins(true);
        $instances = enrol_get_instances($data->courseid, true);
        foreach ($instances as $key => $instance) {
            if (!isset($plugins[$instance->enrol])) {
                unset($instances[$key]);
                continue;
            }
        }

        $usersroles = enrol_get_course_users_roles($data->courseid);
        foreach ($data->unenrol_users as $withroleid) {
            if ($withroleid) {
                $sql = "SELECT ue.*
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                          JOIN {context} c ON (c.contextlevel = :courselevel AND c.instanceid = e.courseid)
                          JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.roleid = :roleid AND ra.userid = ue.userid)";
                $params = array('courseid' => $data->courseid, 'roleid' => $withroleid, 'courselevel' => CONTEXT_COURSE);

            } else {
                // Without any role assigned at course context.
                $sql = "SELECT ue.*
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                          JOIN {context} c ON (c.contextlevel = :courselevel AND c.instanceid = e.courseid)
                     LEFT JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid = ue.userid)
                         WHERE ra.id IS null";
                $params = array('courseid' => $data->courseid, 'courselevel' => CONTEXT_COURSE);
            }

            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (!isset($instances[$ue->enrolid])) {
                    continue;
                }
                $instance = $instances[$ue->enrolid];
                $plugin = $plugins[$instance->enrol];
                if (!$plugin->allow_unenrol($instance) and !$plugin->allow_unenrol_user($instance, $ue)) {
                    continue;
                }

                if ($withroleid && count($usersroles[$ue->userid]) > 1) {
                    // If we don't remove all roles and user has more than one role, just remove this role.
                    role_unassign($withroleid, $ue->userid, $context->id);

                    unset($usersroles[$ue->userid][$withroleid]);
                } else {
                    // If we remove all roles or user has only one role, unenrol user from course.
                    $plugin->unenrol_user($instance, $ue->userid);
                }
                $data->unenrolled[$ue->userid] = $ue->userid;
            }
            $rs->close();
        }
    }
    if (!empty($data->unenrolled)) {
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('unenrol', 'enrol').' ('.count($data->unenrolled).')',
            'error' => false
        );
    }

    $componentstr = get_string('groups');

    // Remove all group members.
    if (!empty($data->reset_groups_members)) {
        groups_delete_group_members($data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('removegroupsmembers', 'group'), 'error' => false);
    }

    // Remove all groups.
    if (!empty($data->reset_groups_remove)) {
        groups_delete_groups($data->courseid, false);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallgroups', 'group'), 'error' => false);
    }

    // Remove all grouping members.
    if (!empty($data->reset_groupings_members)) {
        groups_delete_groupings_groups($data->courseid, false);
        $status[] = array('component' => $componentstr, 'item' => get_string('removegroupingsmembers', 'group'), 'error' => false);
    }

    // Remove all groupings.
    if (!empty($data->reset_groupings_remove)) {
        groups_delete_groupings($data->courseid, false);
        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallgroupings', 'group'), 'error' => false);
    }

    // Look in every instance of every module for data to delete.
    $unsupportedmods = array();
    if ($allmods = $DB->get_records('modules') ) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $modfile = $CFG->dirroot.'/mod/'. $modname.'/lib.php';
            $moddeleteuserdata = $modname.'_reset_userdata';   // Function to delete user data.
            if (file_exists($modfile)) {
                if (!$DB->count_records($modname, array('course' => $data->courseid))) {
                    continue; // Skip mods with no instances.
                }
                include_once($modfile);
                if (function_exists($moddeleteuserdata)) {
                    $modstatus = $moddeleteuserdata($data);
                    if (is_array($modstatus)) {
                        $status = array_merge($status, $modstatus);
                    } else {
                        debugging('Module '.$modname.' returned incorrect staus - must be an array!');
                    }
                } else {
                    $unsupportedmods[] = $mod;
                }
            } else {
                debugging('Missing lib.php in '.$modname.' module!');
            }
            // Update calendar events for all modules.
            course_module_bulk_update_calendar_events($modname, $data->courseid);
        }
    }

    // Mention unsupported mods.
    if (!empty($unsupportedmods)) {
        foreach ($unsupportedmods as $mod) {
            $status[] = array(
                'component' => get_string('modulenameplural', $mod->name),
                'item' => '',
                'error' => get_string('resetnotimplemented')
            );
        }
    }

    $componentstr = get_string('gradebook', 'grades');
    // Reset gradebook,.
    if (!empty($data->reset_gradebook_items)) {
        remove_course_grades($data->courseid, false);
        grade_grab_course_grades($data->courseid);
        grade_regrade_final_grades($data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('removeallcourseitems', 'grades'), 'error' => false);

    } else if (!empty($data->reset_gradebook_grades)) {
        grade_course_reset($data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('removeallcoursegrades', 'grades'), 'error' => false);
    }
    // Reset comments.
    if (!empty($data->reset_comments)) {
        require_once($CFG->dirroot.'/comment/lib.php');
        comment::reset_course_page_comments($context);
    }

    $event = \core\event\course_reset_ended::create($eventparams);
    $event->trigger();

    return $status;
}
?>