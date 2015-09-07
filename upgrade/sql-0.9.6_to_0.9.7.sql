# add recording_id in billing_causes
ALTER TABLE billing_causes ADD COLUMN recording_id int(11);

# fix #20
ALTER TABLE billing_extensions MODIFY sip_num VARCHAR(80);