-- insert a simple host into the IDO for testing
-- only MySQL supported here currently...

SET @hostName = 'localhost';
SET @instance = 'demo';
SET @serviceName = 'syslog';

BEGIN;

TRUNCATE icinga_objects;
TRUNCATE icinga_instances;
TRUNCATE icinga_hoststatus;
TRUNCATE icinga_hosts;

INSERT INTO icinga_instances (instance_id, instance_name) VALUES (1, @instance);

INSERT INTO icinga_objects (object_id, name1, is_active, instance_id, objecttype_id)
VALUES (1, @hostName, 1, 1, 1);

INSERT INTO icinga_hosts (
  host_id, display_name, alias, config_type, host_object_id, instance_id, check_interval, retry_interval
)
VALUES (1, @hostName, @hostName, 0, 1, 1, 60, 10);

INSERT INTO icinga_hoststatus (
  hoststatus_id, instance_id, host_object_id, current_state, output,
  has_been_checked, current_check_attempt, max_check_attempts, state_type,
  last_hard_state, last_check, next_check, last_state_change,
  last_hard_state_change, check_type, notifications_enabled,
  active_checks_enabled, passive_checks_enabled, event_handler_enabled,
  is_reachable, latency, execution_time,
  normal_check_interval, retry_check_interval
)
VALUES (
  1, 1, 1, 0, 'HOST OK',
  1, 1, 5, 0,
  0, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW(),
  NOW(), 0, 1,
  1, 1, 1,
  1, 0.2, 1,
  60, 10
);

INSERT INTO icinga_objects (object_id, name1, name2, is_active, instance_id, objecttype_id)
VALUES (2, @hostName, @serviceName, 1, 1, 2);

INSERT INTO icinga_services (
  service_id, display_name, config_type, host_object_id, service_object_id, instance_id, check_interval, retry_interval
)
VALUES (1, @serviceName, 0, 1, 2, 1, 60, 10);

INSERT INTO icinga_servicestatus (
  servicestatus_id, instance_id, service_object_id, current_state, output,
  has_been_checked, current_check_attempt, max_check_attempts, state_type,
  last_hard_state, last_check, next_check, last_state_change,
  last_hard_state_change, check_type, notifications_enabled,
  active_checks_enabled, passive_checks_enabled, event_handler_enabled,
  is_reachable, latency, execution_time,
  normal_check_interval, retry_check_interval
)
VALUES (
  1, 1, 2, 0, 'SERVICE OK',
     1, 1, 3, 0,
     0, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW(),
               NOW(), 0, 1,
               1, 1, 1,
               1, 0.2, 1,
  60, 10
);

COMMIT;
