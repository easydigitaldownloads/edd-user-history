<?php
/**
 * Functionality for viewing customer history.
 *
 * @package EDD User History
 * @author rzen Media, LLC
 * @license GPL2
 * @link https://rzen.net
 */

class EDDUH_Show_History {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		add_action( 'edd_view_order_details_main_after', array( $this, 'render_metaboxes' ) );
		add_action( 'edd_pre_get_payments', array( $this, 'search_browsing_history' ), 20 );
		add_action( 'edd_add_email_tags', array( $this, 'register_email_tags' ) );

	} /* __construct() */

	/**
	 * Render "Browsing History" and "Purchase History" metaboxes.
	 *
	 * @since 1.5.0
	 */
	public function render_metaboxes( $payment_id = 0 ) {
		$this->do_meta_box( __( 'Customer Browsing History', 'edduh'), $this->render_browsing_history( $payment_id ) );
		$this->do_meta_box( __( 'Customer Purchase History', 'edduh'), $this->render_purchase_history( $payment_id ) );
	} /* render_metaboxes() */

	/**
	 * Wrapper function for generating a metabox-style container on an EDD admin page.
	 *
	 * @since  1.5.0
	 *
	 * @param  string $title    Metabox title.
	 * @param  string $contents Metabox contents.
	 * @return string           HTML markup.
	 */
	private function do_meta_box( $title = '', $contents = '' ) {
		?>
		<div class="postbox">
			<h3 class="hndle"><?php echo $title ?></h3>
			<div class="inside">
				<?php echo $contents; ?>
			</div>
		</div>
		<?php
	} /* do_meta_box() */

	/**
	 * Output browsing history for metabox and email.
	 *
	 * @since 1.5.0
	 *
	 * @param int $payment_id Payment post ID.
	 */
	public function render_browsing_history( $payment_id = 0 ) {

		// If we don't have an actual payment ID, bail now
		if ( ! absint( $payment_id ) ) {
			return false;
		}

		// Grab browsing history
		$payment_meta = edd_get_payment_meta( $payment_id );
		$browsing_history = isset( $payment_meta['user_history'] ) ? rzen_edduh_normalize_history_array( $payment_meta['user_history'] ) : array();

		// Initialize output
		$output = '';

		// Explain this table
		$output .= sprintf( '<p>%s</p>', __( 'Below is every page the customer visited, in order, prior to completing this transaction.', 'edduh' ) );

		// Output user's history (if collected)
		if ( is_array( $browsing_history ) && ! empty( $browsing_history ) ) {

			// Strip off the referring URL
			$referrer = array_shift( $browsing_history );

			// Output the referrer
			$output .= '<p>';
			$output .= sprintf( __( '<strong>Referrer:</strong> %s', 'edduh' ), preg_replace( '/(Referrer:\s)?(http.+)?/', '<a href="$2" target="_blank">$2</a>', $referrer['url'] ) );
			$output .= '</p>';

			// If referrer was a search engine, output the query string the user used
			$search_history = rzen_edduh_get_users_search_query( $referrer['url'] );
			if ( $search_history ) {
				$output .= '<p>' . sprintf( __( 'Original search query: %s', 'edduh' ), '<strong><mark>' . $search_history . '</mark></strong>' ) . '</p>';
			}

			// Output full browsing history
			$output .= '<table style="width:100%; border:1px solid ' . $this->get_admin_color_scheme()[1] . ';" cellpadding="0" cellspacing="0" border="0">';
			$output .= '<tr>';
			$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:left; padding:10px; width:55%;">' . __( 'URL', 'edduh' ) . '</th>';
			$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:left; padding:10px; width:15%;">' . __( 'Timestamp', 'edduh' ) . '</th>';
			$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:right; padding:10px; width:15%;">' . __( 'Time elapsed', 'edduh' ) . '</th>';
			$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:right; padding:10px; width:15%;">' . __( 'Total', 'edduh' ) . '</th>';
			$output .= '</tr>';

			foreach ( $browsing_history as $key => $history ) {

				// Don't output the very last item.
				// This is always the internal 'Order Complete' item.
				if ( end( $browsing_history ) == $history ) {
					continue;
				}

				$alt = $key % 2 ? ' style="background: #f7f7f7;"' : '';
				$output .= '<tr' . $alt . '>';
				$output .= '<td style="text-align:left; padding:10px;">' . ( $key + 1 ) . '. <a href="' . esc_url( $history['url'] ) . '" target="_blank">' . esc_url( $history['url'] ) . '</a></td>';
				if ( $history['time'] ) {
					$output .= '<td style="text-align:left; padding:10px;">' . date( 'Y/m/d \&\n\d\a\s\h\; h:i:sa', ( $history['time'] + get_option( 'gmt_offset' ) * 3600 ) ) . '</td>';
				} else {
					$output .= '<td style="text-align:left; padding:10px;">' . __( 'N/A', 'edduh' ) . '</td>';
				}
				$next = isset( $browsing_history[ $key + 1 ] ) ? $browsing_history[ $key + 1 ] : end( $browsing_history );
				$output .= '<td style="text-align:right; padding:10px;">' . rzen_edduh_calculate_elapsed_time( $history['time'], $next['time'] ) . '</td>';
				$output .= '<td style="text-align:right; padding:10px;">' . rzen_edduh_calculate_elapsed_time( $referrer['time'], $next['time'] ) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>';

			// Output total elapsed time
			$final_entry = end( $browsing_history );
			$output .= '<p>';
			$output .= sprintf( __( '<strong>Total Time Elapsed:</strong> %s', 'edduh' ), rzen_edduh_calculate_elapsed_time( $referrer['time'], $final_entry['time'] ) );
			$output .= '</p>';

		// Otherwise, output that no history was collected
		} else {
			$output .= '<p><em>' . __( 'No page history collected.', 'edduh' ) . '</em></p>';
		}

		// Echo our output
		return $output;

	} /* render_browsing_history() */

	/**
	 * Output customer purchase history.
	 *
	 * @since 1.5.0
	 *
	 * @param int $payment_id Payment post ID.
	 */
	public function render_purchase_history( $payment_id = 0 ) {

		// If we don't have an actual payment ID, bail now
		if ( ! absint( $payment_id ) ) {
			return false;
		}

		// Grab payment details
		$payment_meta = edd_get_payment_meta( $payment_id );

		// Setup important variables
		$lifetime_total   = 0;
		$payments         = get_posts( array(
			'numberposts' => -1,
			'meta_key'    => '_edd_payment_user_email',
			'meta_value'  => $payment_meta['email'],
			'post_type'   => 'edd_payment',
			'order'       => 'ASC',
			'post_status' => 'any',
		) );

		// Initialize output
		$output = '';
		$output .= '<div class="products-header spacing-wrapper clearfix"></div>';
		$output .= '<div class="spacing-wrapper clearfix">';

		// Include a header
		$output .= sprintf( '<p>%s</p>', __( 'Below is every order this customer has completed, including this one (highlighted).', 'edduh' ) );

		// Output purhcase history table
		$output .= '<table style="width:100%; border:1px solid ' . $this->get_admin_color_scheme()[1] . ';" cellpadding="0" cellspacing="0" border="0">';
		$output .= '<tr>';
		$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:left; padding:10px;">' . __( 'Order Number', 'edduh' ) . '</th>';
		$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:left; padding:10px;">' . __( 'Order Date', 'edduh' ) . '</th>';
		$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align:left; padding:10px;">' . __( 'Order Status', 'edduh' ) . '</th>';
		$output .= '<th style="background:' . $this->get_admin_color_scheme()[1] . '; color:#fff; text-align: right; padding:10px;">' . __( 'Order Total', 'edduh' ) . '</th>';
		$output .= '</tr>';

		if ( ! empty( $payments ) ) {
			foreach ( $payments as $key => $payment ) {
				$payment = get_post( $payment->ID );
				$alt = $key % 2 ? ' style="background: #f7f7f7;"' : '';
				$current = $payment->ID == $payment_id ? ' style="background: #ffc; font-weight: bold"' : $alt;

				$output .= '<tr' . $current . '>';
				$output .= '<td style="text-align:left; padding:10px;">' . ( $key + 1 ) . '. <a href="' . admin_url( "edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id={$payment->ID}" ) . '">' . sprintf( __( 'Order %1$s', 'edduh' ), edd_get_payment_number( $payment->ID ) ) . '</a></td>';
				$output .= '<td style="text-align:left; padding:10px;">' . date('Y-m-d h:ia', strtotime( $payment->post_date ) ) . '</td>';
				$output .= '<td style="text-align:left; padding:10px;">' . edd_get_payment_status( $payment, true ) . '</td>';
				$output .= '<td style="text-align:right; padding:10px;">' . edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment->ID ) ) ) . '</td>';
				$output .= '</tr>';

				if ( 'publish' == $payment->post_status ) {
					$lifetime_total += edd_get_payment_amount( $payment->ID );
				}
			}
		}

		$output .= '</table>';

		// Output total lifetime value
		$output .= '<p>';
		$output .= sprintf( __( '<strong>Actual Lifetime Customer Value:</strong> %s', 'edduh' ), '<span style="color:#7EB03B; font-size:1.2em; font-weight:bold;">' . edd_currency_filter( edd_format_amount( $lifetime_total ) ) . '</span>' );
		$output .= '</p>';

		// Close out the container
		$output .= '</div>';

		return $output;

	} /* render_purchase_history() */

	/**
	 * Get current user's admin color scheme.
	 *
	 * @since  1.6.0
	 *
	 * @return array Hexadecimal colors.
	 */
	public function get_admin_color_scheme() {
		global $_wp_admin_css_colors;

		$color_scheme = sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );

		// It's possible to have a color scheme set that is no longer registered.
		if ( empty( $_wp_admin_css_colors[ $color_scheme ] ) ) {
			$color_scheme = 'fresh';
		}

		return $_wp_admin_css_colors[ $color_scheme ]->colors;
	}

	/**
	 * Register custom email tags.
	 *
	 * @since 1.5.0
	 */
	public function register_email_tags() {
		edd_add_email_tag( 'browsing_history', __( 'Display the customer\'s browsing history prior to this transaction.', 'edduh' ), array( $this, 'email_tag_browsing_history' ) );
		edd_add_email_tag( 'purchase_history', __( 'Display the customer\'s purchase history and total lifetime value.', 'edduh' ), array( $this, 'email_tag_purchase_history' ) );
	} /* register_email_tags() */

	/**
	 * Output browsing history via email tag.
	 *
	 * @since  1.5.0
	 *
	 * @param  integer $payment_id Payment post ID.
	 * @return string              HTML markup.
	 */
	public function email_tag_browsing_history( $payment_id = 0 ) {
		$output = '<h2>' . __( 'Customer Browsing History', 'edduh' ) . '</h2>';
		$output .= $this->render_browsing_history( $payment_id );
		return $output;
	} /* email_tag_browsing_history() */

	/**
	 * Output purchase history via email tag.
	 *
	 * @since  1.5.0
	 *
	 * @param  integer $payment_id Payment post ID.
	 * @return string              HTML markup.
	 */
	public function email_tag_purchase_history( $payment_id = 0 ) {
		$output = '<h2>' . __( 'Customer Purchase History', 'edduh' ) . '</h2>';
		$output .= $this->render_purchase_history( $payment_id );
		return $output;
	} /* email_tag_purchase_history() */

	/**
	 * Include browsing history in payment history searches.
	 *
	 * @since  1.5.0
	 *
	 * @param  object $query EDD_Payments_Query object.
	 */
	public function search_browsing_history( $query ) {

		// If not a search query, bail here
		if ( ! isset( $query->args[ 's' ] ) ) {
			return;
		}

		// Add meta_query to search browsing history
		$query->__set( 'meta_query', array(
			'key'     => '_edd_payment_meta',
			'value'   => trim( $query->args[ 's' ] ),
			'compare' => 'LIKE'
		) );

		// Unset search string to prevent searching of transaction title and content
		$query->__unset( 's' );

	} /* search_browsing_history() */

} /* EDDUH_Show_History */
return new EDDUH_Show_History;
