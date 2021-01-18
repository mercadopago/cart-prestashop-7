<?php

// Update method for version 4.4.2 of Mercado Pago module
function upgrade_module_4_4_2($module)
{
	// Execute module update MySQL commands
	$sql_file = dirname(__FILE__).'/sql/install-4.4.2.sql';
	if (!$module->loadSQLFile($sql_file))
        return false;

    //Insert necessary data on DB
    $mp_module = new MPModule();
    $count = $mp_module->where('version', '=', MP_VERSION)->count();

    if ($count == 0) {
        $old_mp = $mp_module->orderBy('id_mp_module', 'desc')->get();
        $old_mp = $mp_module->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(["updated" => true]);
        $mp_module->create(["version" => MP_VERSION]);
    }

	return true;
}
