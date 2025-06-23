<?php
/**
 * Keep in sync certain versions of Rosa2+Style Manager+Nova Blocks.
 */

namespace Pixelgrade\PixelgradeCare\Migrations;

// If this file is called directly, abort.
if ( ! defined( '\ABSPATH' ) ) {
	exit;
}

/**
 * Here is the full story of what we are trying to achieve:
 *
 * - We are targeting the Rosa2 (parent) theme, the Style Manager plugin (replacement for Customify), and the Nova Blocks plugin.
 *   Due to the changes introduced, we decided to keep the logic simple and avoid tons of backwards compatibility code.
 *   Each of the three entities involved will assume that their "peers" are at the appropriate minimum version.
 *   Here, we make sure that that assumption is real!
 *
 * - We will do the work when one of the entities is updated to the minimum required version.
 *   This way we don't force things too much, out-of-sight. When the user updates, we will update/install the peers to their expected versions.
 */

class SyncTheThreeMusketeers {
	/**
	 * The option key for tracking the status of the sync.
	 */
	const STATUS_OPTION_KEY = 'pixelgrade_care_sync_the_three_musketeers_status';

	const STATUS_DOWNLOADED = 'downloaded';
	const STATUS_STARTED = 'started';
	const STATUS_COMPLETED = 'completed';

	/**
	 * The option key for retaining the data of the sync.
	 */
	const DATA_OPTION_KEY = 'pixelgrade_care_sync_the_three_musketeers_data';

	protected $tmp_dir_path = '';

	/**
	 * The details about each one.
	 *
	 * @var array
	 */
	protected $rosa2 = [];
	protected $style_manager = [];
	protected $nova_blocks = [];

	public function __construct() {
	}

	public function init() {
		add_action( 'admin_init', [ $this, 'setup' ], 1 );
		add_action( 'upgrader_process_complete', [ $this, 'setup' ], 1 );
	}

	public function  setup() {
		// If we've completed the sync, bail.
		if ( $this->get_status() === self::STATUS_COMPLETED ) {
			return;
		}

		// If we are in the setup wizard, we don't want to do the sync.
		if ( class_exists( '\PixelgradeCare_SetupWizard' )
		     && \PixelgradeCare_SetupWizard::is_pixelgrade_care_setup_wizard() ) {
			return;
		}

		// Also, still related to the wizard, don't do it in an AJAX call related to installing plugins (via TGMPA)
		// Get the current request action
		$action = pixelgrade_get_current_action();
		if ( ! empty( $action ) && in_array( $action, [ 'install-plugin', 'activate-plugin' ] ) ) {
			return;
		}

		require_once \ABSPATH . 'wp-admin/includes/plugin.php';

		/**
		 * The temporary storage dir path.
		 */
		$upload_config      = \wp_upload_dir();
		$this->tmp_dir_path = \path_join( $upload_config['basedir'], 'pixelgrade_care_tmp' );

		/**
		 * The details about the theme and plugins we are working with.
		 */
		$this->rosa2 = [
			'id' => 'rosa2',
			'type' => 'theme',
			// This is the minimum version we target.
			'minimum_version' => '1.12.0',
			// This is the URL to fetch the .zip file of the minimum required version.
			'source_url' => 'http://wupdates.com/dldcv/themes/07zvbkfzA1kg0d50ldmt7p80znddgmc4k8623tlqjm6d455qknAtqk5ll25h3vsrgy6t45s3pf5d1nlckzqvqk6d48zw3rtj4A5AqArsmrvvg6djgrdgAkzAnzthg7rz4nwAw5spkb3As8ztnz6g3rcjjA7s99ry4yy2szt3kbjv28cwnrgfmvtxhA/JxLn7/',
			// The theme slug/stylesheet (the theme directory name).
			'file' => 'rosa2',
			// A callable (function, class static method) to check if the theme is active.
			// Takes precedence over other ways to check.
			'callable_active' => 'rosa2_setup',
		];

		$this->style_manager = [
			'id' => 'style_manager',
			'type' => 'plugin',
			'minimum_version' => '2.0.0',
			'source_url' => 'https://downloads.wordpress.org/plugin/style-manager.zip',
			// Path to the plugin file relative to the plugins directory.
			'file' => 'style-manager/style-manager.php',
			// A callable (function, class static method) to check if the plugin is active.
			// Takes precedence over other ways to check.
			'callable_active' => '\Pixelgrade\StyleManager\plugin',
		];

		$this->nova_blocks = [
			'id' => 'nova_blocks',
			'type' => 'plugin',
			'minimum_version' => '1.13.0',
			// We use this URL since we don't want to end up with the 2.0.0 version (that is not supported yet).
			'source_url' => 'https://downloads.wordpress.org/plugin/nova-blocks.1.13.4.zip',
			// Path to the plugin file relative to the plugins directory.
			'file' => 'nova-blocks/nova-blocks.php',
			// A callable (function, class static method) to check if the plugin is active.
			// Takes precedence over other ways to check.
			'callable_active' => '\novablocks_get_plugin_path',
		];

		// If the current (parent) theme is not Rosa2, bail since we have nothing to do.
		if ( ! $this->check_active( $this->rosa2 ) ) {
			return;
		}

		/**
		 * Check if all the conditions are met.
		 */
		if ( $this->all_good() ) {
			return;
		}

		// If we don't have an option value, we start by downloading the zips to a temporary location.
		if ( ! $this->get_status() ) {
			// We need to NOT start downloading until the updated versions are actually published.
			// We will use the Nova Blocks zip on WordPress.org as the telltale sign that things are in motion.
			// This is different from the URL used to download NovaBlocks since that one will return the latest version
			// that may be greater than 1.12.0.
			$raw_response = wp_remote_head( 'https://downloads.wordpress.org/plugin/nova-blocks.1.12.0.zip' );
			if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				return;
			}

			try {
				$this->download_temporary_artifacts();
			} catch ( \Exception $e ) {
				// Ignore exceptions.
			}

			\update_option( self::STATUS_OPTION_KEY, self::STATUS_DOWNLOADED, true );

		} else if ( $this->get_status() === self::STATUS_DOWNLOADED && \current_user_can( 'update_plugins' ) ) {
			// If neither the theme or Nova Blocks hasn't been updated to the minimum version,
			// we lie and wait for the user to make the first step.
			if ( ! ( $this->check_active( $this->rosa2 ) && $this->check_minimum_version( $this->rosa2 ) )
				&& ! ( $this->check_active( $this->nova_blocks ) && $this->check_minimum_version( $this->nova_blocks ) ) ) {

				return;
			}

			// Now, we need to see what we need to install/activate/update.
			\update_option( self::STATUS_OPTION_KEY, self::STATUS_STARTED, true );

			// Deal with Nova Blocks.
			if ( ! $this->check_installed( $this->nova_blocks )
			     || ! $this->check_minimum_version( $this->nova_blocks ) ) {

				$this->install_plugin( $this->nova_blocks );
			}

			// Deal with Style Manager/Customify.
			// Style Manager will automatically deactivate Customify when it's active.
			if ( ! $this->check_installed( $this->style_manager )
			     || ! $this->check_minimum_version( $this->style_manager ) ) {

				$this->install_plugin( $this->style_manager );
			}

			// Deal with Rosa2.
			if ( ! $this->check_minimum_version( $this->rosa2 ) ) {

				$this->install_theme( $this->rosa2 );
			}

			// We remember that we've completed this so we don't do it ever again.
			\update_option( self::STATUS_OPTION_KEY, self::STATUS_COMPLETED, true );

			// Fire a specific action to allow for custom logic to hook in.
			do_action( 'pixelgrade/did_auto_install_or_update' );

			// Cleanup downloaded artifacts.
			$this->cleanup();

			return;
		}
	}

	/**
	 * Checks the current state of all the entities involved.
	 *
	 * @return bool True if we have nothing to do, false if there is work to do.
	 */
	protected function all_good() {
		// Check that Rosa2 is active and at the right version.
		if ( ! $this->check_active( $this->rosa2 ) ) {
			return false;
		}
		if ( ! $this->check_minimum_version( $this->rosa2 ) ) {
			return false;
		}

		// Check that Style Manager is active and at the right version.
		if ( ! $this->check_active( $this->style_manager ) ) {
			return false;
		}
		if ( ! $this->check_minimum_version( $this->style_manager ) ) {
			return false;
		}

		// Check that Nova Blocks is active and at the right version.
		if ( ! $this->check_active( $this->nova_blocks ) ) {
			return false;
		}
		if ( ! $this->check_minimum_version( $this->nova_blocks ) ) {
			return false;
		}

		// If we reached this point we are good.
		return true;
	}

	/**
	 * Check if a given entity is installed based on its configuration.
	 *
	 * @param array $config The entity configuration.
	 *
	 * @return bool
	 */
	protected function check_installed( $config ) {
		// If it is active, it is definitely installed.
		if ( $this->check_active( $config ) ) {
			return true;
		}

		$file = $this->get_file( $config );
		if ( ! empty( $file ) ) {
			if ( ! empty( $config['type'] ) && 'theme' === $config['type'] ) {
				$themes = \wp_get_themes();
				return isset( $themes[ $file ] );
			} else {
				// Assume it's a plugin.
				if ( ! function_exists( '\get_plugins' ) ) {
					require_once \ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$plugins = \get_plugins();
				$file = \plugin_basename( $file );
				return isset( $plugins[ $file ] );
			}
		}

		return false;
	}

	/**
	 * Check if a given entity is active based on its configuration.
	 *
	 * @param array $config The entity configuration.
	 *
	 * @return bool
	 */
	protected function check_active( $config ) {
		if ( ! empty( $config['callable_active'] ) ) {
			return is_callable( $config['callable_active'] );
		}

		$file = $this->get_file( $config );
		if ( ! empty( $file ) ) {
			if ( ! empty( $config['type'] ) && 'theme' === $config['type'] ) {
				// We are only interested in parent themes.
				return $file === \get_template();
			} else {
				// Assume it's a plugin.
				return \is_plugin_active( \plugin_basename( $config['file'] ) );
			}
		}

		return false;
	}

	/**
	 * Get the file of a given entity based on its configuration.
	 *
	 * @param array $config The entity configuration.
	 *
	 * @return string|false
	 */
	protected function get_file( $config ) {
		$file = false;
		if ( ! empty( $config['callable_file'] ) && is_callable( $config['callable_file'] ) ) {
			try {
				$file = call_user_func( $config['callable_file'] );
			} catch ( \Exception $e ) {
				// Just ignore it.
			}
		}
		if ( empty( $file ) && ! empty( $config['file'] ) ) {
			$file = $config['file'];
		}

		return $file;
	}

	/**
	 * Check if a given entity is at the minimum version based on its configuration.
	 *
	 * @param array $config The entity configuration.
	 *
	 * @return bool
	 */
	protected function check_minimum_version( $config ) {
		$file = $this->get_file( $config );
		if ( empty( $file ) ) {
			return false;
		}

		$minimum_version = '0';
		if ( ! empty( $config['minimum_version'] ) ) {
			$minimum_version = trim( $config['minimum_version'] );
		}

		if ( ! empty( $config['type'] ) && 'theme' === $config['type'] ) {
			$theme = \wp_get_theme( $file );
			if ( ! $theme->exists() ) {
				return false;
			}

			return \version_compare( $theme->get('Version'), $minimum_version . '-RC0', '>=' );
		} else {
			// Assume it's a plugin.
			$absolute_path = \path_join( \WP_PLUGIN_DIR, $file );
			// First check that the plugin is actually installed.
			if ( ! file_exists( $absolute_path ) ) {
				return false;
			}

			$plugin = \get_plugin_data( $absolute_path );

			return \version_compare( $plugin['Version'], $minimum_version . '-RC0', '>=' );
		}
	}

	/**
	 * Returns the status of the sync.
	 *
	 * @return false|string false if the sync hasn't been started.
	 *                      "downloaded" if the zips have been downloaded to the temporary directory.
	 *                      "started" if it has but hasn't completed.
	 *                      "completed" if it has been completed.
	 */
	protected function get_status() {
		return \get_option( self::STATUS_OPTION_KEY );
	}

	protected function download_temporary_artifacts() {
		// We want to download the Style Manager plugin that replaces Customify.
		// But only if it is not already installed and at the right version.
		if ( ( ! $this->check_installed( $this->style_manager ) || ! $this->check_minimum_version( $this->style_manager ) )
		     && ! empty( $this->style_manager['source_url'] )
			 && ! $this->check_download_source( $this->style_manager ) ) {

			$this->download_source( $this->style_manager );
		}

		// Handle Nova Blocks.
		if ( ( ! $this->check_installed( $this->nova_blocks ) || ! $this->check_minimum_version( $this->nova_blocks ) )
		     && ! empty( $this->nova_blocks['source_url'] )
		     && ! $this->check_download_source( $this->nova_blocks ) ) {

			$this->download_source( $this->nova_blocks );
		}

		// Handle Rosa2.
		if ( ( ! $this->check_installed( $this->rosa2 ) || ! $this->check_minimum_version( $this->rosa2 ) )
		     && ! empty( $this->rosa2['source_url'] )
		     && ! $this->check_download_source( $this->rosa2 ) ) {

			$this->download_source( $this->rosa2 );
		}

		return true;
	}

	protected function download_source( $config ) {
		$filename = $this->get_path_to_temp_source( $config );

		// Make sure that the directory tree is in place.
		if ( ! \wp_mkdir_p( \dirname( $filename ) ) ) {
			return false;
		}

		$tmpfname = $this->download_url( $config['source_url'] );

		if ( ! rename( $tmpfname, $filename ) ) {
			return false;
		}

		$sync_data = \get_option( self::DATA_OPTION_KEY, [] );
		if ( empty( $sync_data['downloads'] ) ) {
			$sync_data['downloads'] = [];
		}
		$sync_data['downloads'][ $config['id'] ] = $filename;
		\update_option( self::DATA_OPTION_KEY, $sync_data );

		return true;
	}

	/**
	 * Check if the zip was downloaded to the temporary location.
	 *
	 * @param array $config The entity configuration.
	 *
	 * @return bool
	 */
	protected function check_download_source( $config ) {

		return file_exists( $this->get_path_to_temp_source( $config ) );
	}

	protected function get_path_to_temp_source( $config ) {
		return $this->get_absolute_path_to_tmpdir( $config['id'] . '.zip' );
	}

	/**
	 * Given an URL download it to a temporary location and provide the path to the resulting file.
	 *
	 * @param string $url
	 *
	 * @return string The temporary file path.
	 */
	public function download_url( $url ) {
		// Since this is a local URL to a file, we don't need to download, just to create a temporary copy.
		if ( $this->is_local_url( $url ) && $path = $this->local_url_to_path( $url ) ) {
			$url_filename = basename( $path );
			$tmpfname = \wp_tempnam( $url_filename );
			if ( ! $tmpfname ) {
				return false;
			}

			if ( false === copy( $path, $tmpfname ) ) {
				return false;
			}

			// Return the path to the temporary file.
			return $tmpfname;
		}

		require_once \ABSPATH . 'wp-admin/includes/file.php';
		$tmpfname = \download_url( $url );
		if ( \is_wp_error( $tmpfname ) ) {
			return false;
		}

		// Return the path to the temporary file.
		return $tmpfname;
	}

	/**
	 * Given an URL determine if it is a local one (has the same host as the WP install).
	 *
	 * @see wp_http_validate_url()
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function is_local_url( $url ) {
		$original_url = $url;
		$url          = \wp_kses_bad_protocol( $url, [ 'http', 'https' ] );
		if ( ! $url || strtolower( $url ) !== strtolower( $original_url ) ) {
			return false;
		}

		$parsed_url = parse_url( $url );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			return false;
		}

		$parsed_home = parse_url( get_option( 'home' ) );
		if ( isset( $parsed_home['host'] ) ) {
			return strtolower( $parsed_home['host'] ) === strtolower( $parsed_url['host'] );
		}

		return false;
	}

	/**
	 * Given a local file URL convert it to an absolute path.
	 *
	 * @see wp_http_validate_url()
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function local_url_to_path( $url ) {
		$parsed_url = parse_url( $url );

		if ( empty( $parsed_url['path'] ) ) {
			return '';
		}

		$file = \ABSPATH . ltrim( $parsed_url['path'], '/' );
		if ( file_exists( $file ) ) {
			return $file;
		}

		return '';
	}

	/**
	 * Retrieve the absolute path to a file stored in our temporary directory.
	 *
	 * @param string $path Optional. Relative path.
	 *
	 * @return string
	 */
	protected function get_absolute_path_to_tmpdir( $path = '' ) {
		return \path_join( $this->tmp_dir_path, ltrim( $path, '/' ) );
	}

	/**
	 * Install and activate plugin.
	 *
	 * @param array $config The plugin configuration.
	 *
	 * @return bool
	 */
	protected function install_plugin( $config ) {
		if ( ! \current_user_can( 'update_plugins' ) || empty( $config['source_url'] ) ) {
			return false;
		}

		$filename = $this->get_file( $config );
		if ( empty( $filename ) ) {
			return false;
		}
		// If we have a .git file in the directory, we will not attempt to install since it will probably fail due to writing permissions.
		$absolute_file_path = \path_join( \WP_PLUGIN_DIR, $filename );
		if ( \file_exists( $absolute_file_path )
		     && \file_exists( \path_join( plugin_dir_path( $absolute_file_path ), '.git' ) ) ) {

			return false;
		}

		require_once \ABSPATH . 'wp-admin/includes/file.php';

		$skin = new Blank_Plugin_Installer_Skin(
			[
				'type'  => 'plugin',
				'nonce' => \wp_nonce_url( $config['source_url'] ),
			]
		);

		// Check if we have locally downloaded zip to use.
		$sync_data = \get_option( self::DATA_OPTION_KEY, [] );
		if ( ! empty( $sync_data['downloads'][ $config['id'] ] )
		     && file_exists( $sync_data['downloads'][ $config['id'] ] ) ) {
			$config['source_url'] = $sync_data['downloads'][ $config['id'] ];
		}

		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $config['source_url'], [
			'overwrite_package'  => true, // Overwrite existing files.
		] );

		if ( \is_wp_error( $result ) || null === $result ) {
			return false;
		}

		\wp_cache_flush();

		// We need it to be active.
		return $this->activate_plugin( $filename );
	}

	/**
	 * Activate plugin.
	 *
	 * @param string $filename Plugin main file name.
	 *
	 * @return bool
	 */
	protected function activate_plugin( $filename ) {
		// Network activate only if on network admin pages.
		$result = \is_network_admin() ? \activate_plugin( $filename, null, true ) : \activate_plugin( $filename );

		if ( \is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Install theme.
	 *
	 * @param array $config The plugin configuration.
	 *
	 * @return bool
	 */
	protected function install_theme( $config ) {
		if ( ! \current_user_can( 'update_themes' ) || empty( $config['source_url'] ) ) {
			return false;
		}

		$filename = $this->get_file( $config );
		if ( empty( $filename ) ) {
			return false;
		}
		// If we have a .git file in the directory, we will not attempt to install since it will probably fail due to writing permissions.
		$absolute_file_path = \path_join( \get_theme_root(), $filename );
		if ( \file_exists( $absolute_file_path )
		     && \file_exists( \path_join( \plugin_dir_path( $absolute_file_path ), '.git' ) ) ) {

			return false;
		}

		require_once \ABSPATH . 'wp-admin/includes/file.php';

		$skin = new Blank_Theme_Installer_Skin(
			[
				'type'  => 'theme',
				'nonce' => \wp_nonce_url( $config['source_url'] ),
			]
		);

		// Check if we have locally downloaded zip to use.
		$sync_data = \get_option( self::DATA_OPTION_KEY, [] );
		if ( ! empty( $sync_data['downloads'][ $config['id'] ] )
		     && file_exists( $sync_data['downloads'][ $config['id'] ] ) ) {
			$config['source_url'] = $sync_data['downloads'][ $config['id'] ];
		}

		$upgrader = new \Theme_Upgrader( $skin );
		$result   = $upgrader->install( $config['source_url'], [
			'overwrite_package'  => true, // Overwrite existing files.
		] );

		if ( \is_wp_error( $result ) || null === $result ) {
			return false;
		}

		\wp_cache_flush();

		// We need it to be active.
		return $this->activate_plugin( $filename );
	}

	protected function cleanup() {
		if ( $this->check_download_source( $this->style_manager ) ) {
			unlink( $this->get_path_to_temp_source( $this->style_manager ) );
		}

		if ( $this->check_download_source( $this->nova_blocks ) ) {
			unlink( $this->get_path_to_temp_source( $this->nova_blocks ) );
		}

		if ( $this->check_download_source( $this->rosa2 ) ) {
			unlink( $this->get_path_to_temp_source( $this->rosa2 ) );
		}
	}
}

require_once \ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/**
 * Class Blank_Plugin_Installer_Skin
 *
 * Just a skin that doesn't do anything.
 */
class Blank_Plugin_Installer_Skin extends \Plugin_Installer_Skin {
	public function header() {
	}

	public function footer() {
	}

	public function error( $errors ) {
	}

	public function feedback( $string, ...$args ) {
	}
}

/**
 * Class Blank_Theme_Installer_Skin
 *
 * Just a skin that doesn't do anything.
 */
class Blank_Theme_Installer_Skin extends \Theme_Installer_Skin {
	public function header() {
	}

	public function footer() {
	}

	public function error( $errors ) {
	}

	public function feedback( $string, ...$args ) {
	}
}
