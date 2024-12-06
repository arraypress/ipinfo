<?php
/**
 * Plugin Name:       ArrayPress - IPInfo Tester
 * Plugin URI:        https://arraypress.com/plugins/ipinfo-tester
 * Description:       A plugin to test and demonstrate the IPInfo.io API integration.
 * Author:           ArrayPress
 * Author URI:       https://arraypress.com
 * License:          GNU General Public License v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      arraypress-ipinfo
 * Domain Path:      /languages/
 * Requires PHP:     7.4
 * Requires at least: 6.2.2
 * Version:          1.0.0
 */

namespace ArrayPress\IPInfo;

// Exit if accessed directly
use ArrayPress\Utils\IP\IPInfoClient;

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'IPINFO_PLUGIN_FILE', __FILE__ );
define( 'IPINFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPINFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin class to handle all the functionality
 */
class Plugin {

	/**
	 * Instance of IPInfoClient
	 *
	 * @var Client|null
	 */
	private ?Client $client = null;

	/**
	 * Initialize the plugin
	 */
	public function __construct() {
		// Register autoloader
		spl_autoload_register( [ $this, 'autoloader' ] );

		// Initialize client if token is set
		$token = get_option( 'ipinfo_api_token' );
		if ( $token ) {
			$this->client = new Client( $token );
		}

		// Hook into WordPress
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * PSR-4 autoloader for plugin classes
	 *
	 * @param string $class The class name to load
	 */
	public function autoloader( string $class ) {
		// Check if the class is in our namespace
		if ( strpos( $class, 'ArrayPress\\Utils\\IP\\' ) !== 0 ) {
			return;
		}

		// Remove namespace from class name
		$class_file = str_replace( 'ArrayPress\\Utils\\IP\\', '', $class );

		// Build file path
		$file = IPINFO_PLUGIN_DIR . 'src/' . $class_file . '.php';

		// Include file if it exists
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu() {
		add_management_page(
			'IPInfo Tester',
			'IPInfo Tester',
			'manage_options',
			'ipinfo-tester',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting( 'ipinfo_settings', 'ipinfo_api_token' );

		add_settings_section(
			'ipinfo_settings_section',
			'API Settings',
			null,
			'ipinfo-tester'
		);

		add_settings_field(
			'ipinfo_api_token',
			'IPInfo API Token',
			[ $this, 'render_token_field' ],
			'ipinfo-tester',
			'ipinfo_settings_section'
		);
	}

	/**
	 * Render API token field
	 */
	public function render_token_field() {
		$token = get_option( 'ipinfo_api_token' );
		echo '<input type="text" name="ipinfo_api_token" value="' . esc_attr( $token ) . '" class="regular-text">';
	}

	/**
	 * Render admin page
	 */
	/**
	 * Render admin page
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		// Get test IP if submitted
		$test_ip = isset( $_POST['test_ip'] ) ? sanitize_text_field( $_POST['test_ip'] ) : '';
		$results = null;

		if ( $test_ip && $this->client ) {
			$results = $this->client->get_ip_info( $test_ip );
		}
		?>
        <div class="wrap">
            <h1>IPInfo Tester</h1>

            <form method="post" action="options.php">
				<?php
				settings_fields( 'ipinfo_settings' );
				do_settings_sections( 'ipinfo-tester' );
				submit_button( 'Save API Token' );
				?>
            </form>

            <hr>

            <h2>Test IP Address</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="test_ip">IP Address</label></th>
                        <td>
                            <input type="text" name="test_ip" id="test_ip" value="<?php echo esc_attr( $test_ip ); ?>"
                                   class="regular-text">
							<?php submit_button( 'Test IP', 'secondary', 'submit', false ); ?>
                        </td>
                    </tr>
                </table>
            </form>

			<?php if ( $results ): ?>
                <h2>Results</h2>
				<?php if ( is_wp_error( $results ) ): ?>
                    <div class="notice notice-error">
                        <p><?php echo esc_html( $results->get_error_message() ); ?></p>
                    </div>
				<?php else: ?>
                    <table class="widefat striped">
                        <tbody>
                        <!-- Basic Information -->
                        <tr>
                            <th>IP Address</th>
                            <td><?php echo esc_html( $results->get_ip() ); ?></td>
                        </tr>

                        <!-- Location Information -->
                        <tr>
                            <th>Location</th>
                            <td>
								<?php
								$parts = [];
								if ( $results->get_city() ) {
									$parts[] = esc_html( $results->get_city() );
								}
								if ( $results->get_region() ) {
									$parts[] = esc_html( $results->get_region() );
								}
								if ( $results->get_country() ) {
									$parts[] = esc_html( $results->get_country() );
								}
								if ( $results->get_postal() ) {
									$parts[] = 'Postal: ' . esc_html( $results->get_postal() );
								}
								echo implode( ', ', $parts );
								?>
                            </td>
                        </tr>

                        <!-- Coordinates -->
						<?php if ( $coords = $results->get_coordinates() ): ?>
                            <tr>
                                <th>Coordinates</th>
                                <td><?php echo esc_html( "Lat: {$coords['latitude']}, Long: {$coords['longitude']}" ); ?></td>
                            </tr>
						<?php endif; ?>

                        <!-- Timezone -->
						<?php if ( $results->get_timezone() ): ?>
                            <tr>
                                <th>Timezone</th>
                                <td><?php echo esc_html( $results->get_timezone() ); ?></td>
                            </tr>
						<?php endif; ?>

                        <!-- Company/Organization Information -->
						<?php if ( $results->get_company() || $results->get_org() ): ?>
                            <tr>
                                <th>Organization</th>
                                <td>
									<?php
									if ( $company = $results->get_company() ) {
										if ( $company->get_name() ) {
											echo 'Name: ' . esc_html( $company->get_name() ) . '<br>';
										}
										if ( $company->get_domain() ) {
											echo 'Domain: ' . esc_html( $company->get_domain() ) . '<br>';
										}
										if ( $company->get_type() ) {
											echo 'Type: ' . esc_html( $company->get_type() );
										}
									} elseif ( $org = $results->get_org() ) {
										echo esc_html( $org );
									}
									?>
                                </td>
                            </tr>
						<?php endif; ?>

                        <!-- ASN Information -->
						<?php if ( $asn = $results->get_asn() ): ?>
                            <tr>
                                <th>ASN Information</th>
                                <td>
									<?php
									if ( $asn->get_asn() ) {
										echo 'ASN: ' . esc_html( $asn->get_asn() ) . '<br>';
									}
									if ( $asn->get_name() ) {
										echo 'Name: ' . esc_html( $asn->get_name() ) . '<br>';
									}
									if ( $asn->get_domain() ) {
										echo 'Domain: ' . esc_html( $asn->get_domain() ) . '<br>';
									}
									if ( $asn->get_route() ) {
										echo 'Route: ' . esc_html( $asn->get_route() ) . '<br>';
									}
									if ( $asn->get_type() ) {
										echo 'Type: ' . esc_html( $asn->get_type() );
									}
									?>
                                </td>
                            </tr>
						<?php endif; ?>

                        <!-- Privacy Information -->
						<?php if ( $privacy = $results->get_privacy() ): ?>
                            <tr>
                                <th>Privacy Detection</th>
                                <td>
									<?php
									echo 'VPN: ' . ( $privacy->is_vpn() ? 'Yes' : 'No' ) . '<br>';
									echo 'Proxy: ' . ( $privacy->is_proxy() ? 'Yes' : 'No' ) . '<br>';
									echo 'Tor: ' . ( $privacy->is_tor() ? 'Yes' : 'No' ) . '<br>';
									echo 'Relay: ' . ( $privacy->is_relay() ? 'Yes' : 'No' ) . '<br>';
									echo 'Hosting: ' . ( $privacy->is_hosting() ? 'Yes' : 'No' );
									if ( $service = $privacy->get_service() ) {
										echo '<br>Service: ' . esc_html( $service );
									}
									?>
                                </td>
                            </tr>
						<?php endif; ?>

                        <!-- Abuse Information -->
						<?php if ( $abuse = $results->get_abuse() ): ?>
                            <tr>
                                <th>Abuse Contact</th>
                                <td>
									<?php
									if ( $abuse->get_name() ) {
										echo 'Name: ' . esc_html( $abuse->get_name() ) . '<br>';
									}
									if ( $abuse->get_email() ) {
										echo 'Email: ' . esc_html( $abuse->get_email() ) . '<br>';
									}
									if ( $abuse->get_phone() ) {
										echo 'Phone: ' . esc_html( $abuse->get_phone() ) . '<br>';
									}
									if ( $abuse->get_network() ) {
										echo 'Network: ' . esc_html( $abuse->get_network() ) . '<br>';
									}
									if ( $abuse->get_address() ) {
										echo 'Address: ' . esc_html( $abuse->get_address() ) . '<br>';
									}
									if ( $abuse->get_country() ) {
										echo 'Country: ' . esc_html( $abuse->get_country() );
									}
									?>
                                </td>
                            </tr>
						<?php endif; ?>

                        <!-- Domains Information (Premium) -->
						<?php if ( $domains = $results->get_domains() ): ?>
                            <tr>
                                <th>Associated Domains</th>
                                <td>
									<?php
									echo 'Total Domains: ' . esc_html( $domains->get_total() ) . '<br>';
									if ( $domain_list = $domains->get_domains() ) {
										echo 'Domains:<br>';
										foreach ( $domain_list as $domain ) {
											echo '- ' . esc_html( $domain ) . '<br>';
										}
									}
									?>
                                </td>
                            </tr>
						<?php endif; ?>

                        </tbody>
                    </table>
				<?php endif; ?>
			<?php endif; ?>
        </div>
		<?php
	}
}

// Initialize the plugin
new Plugin();