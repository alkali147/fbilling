# add recording_id in billing_causes
ALTER TABLE asterisk.billing_causes ADD COLUMN billing_causes.recording_id int(11);

# fix #20
ALTER TABLE asterisk.billing_extensions MODIFY billing_extensions.sip_num VARCHAR(80);

# version 0.9.9
ALTER TABLE asterisk.billing_extensions ADD COLUMN billing_extensions.is_active int(11) NOT NULL DEFAULT 1;