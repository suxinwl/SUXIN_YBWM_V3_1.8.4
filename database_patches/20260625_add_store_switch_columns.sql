-- MySQL 5.6 compatible patch for store switch columns used by the V3 admin/channel code.
SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `ybwm_v3_store` ADD COLUMN `expressSwitch` int(11) NOT NULL DEFAULT ''0'' COMMENT ''express switch'' AFTER `paySwitch`',
    'SELECT ''ybwm_v3_store.expressSwitch already exists'' AS info')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'ybwm_v3_store'
    AND COLUMN_NAME = 'expressSwitch'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `ybwm_v3_store` ADD COLUMN `differentPlacesSwitch` int(11) NOT NULL DEFAULT ''0'' COMMENT ''different places switch'' AFTER `expressSwitch`',
    'SELECT ''ybwm_v3_store.differentPlacesSwitch already exists'' AS info')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'ybwm_v3_store'
    AND COLUMN_NAME = 'differentPlacesSwitch'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
