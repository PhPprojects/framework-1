<?php
/**
 * Constants
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

/**
 * PREFER to be used in Database calls or storing Session data, default is 'nova_'
 */
define('PREFIX', 'nova_');

/**
 * Setup the Config API Mode.
 * For using the 'database' mode, you need to have a database, with a table generated by 'scripts/nova_options'
 */
define('CONFIG_STORE', 'files'); // Supported: "files", "database"
