# add recording_id in billing_causes
ALTER TABLE asterisk.billing_causes ADD COLUMN billing_causes.recording_id int(11);

# fix #20
ALTER TABLE asterisk.billing_extensions MODIFY billing_extensions.sip_num VARCHAR(80);

# version 0.9.9
ALTER TABLE asterisk.billing_extensions ADD COLUMN billing_extensions.is_active int(11) NOT NULL DEFAULT 1;
INSERT INTO asterisk.billing_causes (`id`, `name`, `recording_id`) VALUES (13, 'Extension is not active', 0);

# 1.0.0 to 1.1.0
CREATE TABLE IF NOT EXISTS `billing_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `extension_id` int(11) NOT NULL,
    `tenant_id` int(11) NOT NULL,
    `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `amount` varchar(8) NOT NULL DEFAULT 0,
    `comment` varchar(256) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE asterisk.billing_tenants ADD COLUMN billing_tenants.credit float NOT NULL;
INSERT INTO billing_causes (`id`,`name`,`recording_id`) VALUES ('14','Tenant has no credit','0');
ALTER TABLE billing_extensions ADD COLUMN personal_credit int(1) DEFAULT '1';

