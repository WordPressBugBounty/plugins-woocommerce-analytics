<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Analytics\Internal;

use Automattic\WooCommerce\Analytics\HelperTraits\LoggerTrait;
use Automattic\WooCommerce\Analytics\Internal\DI\Configuration as DIConfiguration;
use Automattic\WooCommerce\Analytics\Internal\DI\RegistrableInterface;
use Automattic\WooCommerce\Analytics\Internal\Requirements\PluginValidator;
use Automattic\WooCommerce\Analytics\Logging\LoggerInterface;
use Exception;
use Automattic\WooCommerce\Analytics\Dependencies\Psr\Container\ContainerExceptionInterface;
use Automattic\WooCommerce\Analytics\Dependencies\Psr\Container\ContainerInterface;
use Automattic\WooCommerce\Analytics\Dependencies\DI\ContainerBuilder;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Analytics\Internal\Jetpack\Sync\Configuration as JetpackConfiguration;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin implements RegistrableInterface {

	use LoggerTrait;

	/**
	 * The instance of the Plugin object.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * The DI container.
	 *
	 * @var ContainerInterface
	 */
	private ContainerInterface $container;

	/**
	 * Set the DI container.
	 *
	 * @param ContainerInterface $container The DI container.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;

		$this->init_hooks();
		$this->init_jetpack_configuration();
	}

	/**
	 * Get the instance of the Plugin object.
	 *
	 * @return Plugin
	 *
	 * @throws Exception If the DI container cannot be built.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			$container_builder = new ContainerBuilder();
			$container_builder->addDefinitions( DIConfiguration::get_php_di_configuration() );
			$container      = $container_builder->build();
			self::$instance = $container->get( self::class );
		}

		return self::$instance;
	}

	/**
	 * Initialize the hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		register_activation_hook(
			WC_ANALYTICS_FILE,
			array( $this, 'activate' )
		);

		// Declare compatibility with HPOS.
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		/**
		 * Check if WC_Site_Tracking exits and if not, create an alias for it.
		 * This is needed to avoid fatal errors when sending or opening email notes.
		 * See https://github.com/woocommerce/woocommerce/pull/51525 that is not yet released.
		 */
		add_action(
			'plugins_loaded',
			function () {
				if ( class_exists( 'WC_Site_Tracking' ) && ! class_exists( 'Automattic\WooCommerce\Admin\Notes\WC_Site_Tracking' ) ) {
					class_alias( 'WC_Site_Tracking', 'Automattic\WooCommerce\Admin\Notes\WC_Site_Tracking' );
				}
			},
			5
		);

		// Register the logger after WooCommerce is loaded since it's a dependency of WooCommerce.
		add_action( 'woocommerce_loaded', array( $this, 'initialize_logger' ) );

		// Hook much of our plugin after WooCommerce is initialized.
		add_action( 'woocommerce_init', array( $this, 'register' ) );

		// Allow WooCommerce core to ask about translations updates for our plugin.
		add_filter( 'woocommerce_translations_updates_for_woocommerce-analytics', '__return_true' );
	}

	/**
	 * Initialize the logger.
	 *
	 * @return void
	 */
	public function initialize_logger(): void {
		$logger = $this->container->get( LoggerInterface::class );
		$this->set_logger( $logger );
	}

	/**
	 * Register main plugin functionality.
	 *
	 * @return void
	 */
	public function register(): void {
		// Ensure the plugin requirements are met.
		if ( ! PluginValidator::validate() ) {
			return;
		}

		try {
			$registrables = $this->container->get( RegistrableInterface::class );
			foreach ( $registrables as $service ) {
				$service->register();
			}
		} catch ( ContainerExceptionInterface $e ) {
			$this->get_logger()->log_error( 'Failed to register services.', __METHOD__ );
		}
	}

	/**
	 * Initialize Jetpack configuration before plugins_loaded.
	 *
	 * @return void
	 */
	private function init_jetpack_configuration(): void {
		$jetpack_config = $this->container->get( JetpackConfiguration::class );
		$jetpack_config->register();
	}

	/**
	 * Hooked to register_activation_hook() by an anonymous function in the plugin file.
	 *
	 * @return void
	 */
	public function activate(): void {
		// For firing one-off events immediately after activation.
		set_transient( 'activated_woocommerce_analytics', true, 15 * MINUTE_IN_SECONDS );
	}

	/**
	 * Declare compatibility with HPOS.
	 *
	 * @return void
	 */
	public function declare_compatibility(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_ANALYTICS_FILE, true );
		}
	}
}
