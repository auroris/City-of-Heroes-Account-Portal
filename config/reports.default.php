<?php

// How to create custom reports for yourself:
// Make a file called 'reports.user.php' with entries similar to what's below.
// Replacement strings: ':ACCOUNT_NAME', ':CHARACTER_NAME', ':ACCOUNT_UID', ':CHARACTER_CID'

$reports['CharacterSalvage']['sql'] = "
SELECT 'Inventory' as Type, *
	FROM cohdb.dbo.InvSalvage0 WHERE ContainerId = :CHARACTER_CID
UNION
SELECT 'Stored' AS Type, *, NULL AS S_ExperiementalTech,
	NULL AS S_UnknownChemicals,
	NULL AS S_SignatureSalvage,
	NULL AS S_SignatureSalvageU,
	NULL AS S_Never_MeltingIce
	FROM cohdb.dbo.InvStoredSalvage0 WHERE ContainerId = :CHARACTER_CID"; // 415 columns(!!!)
$reports['CharacterSalvage']['transpose'] = true;   // We definitely want this report rotated.
