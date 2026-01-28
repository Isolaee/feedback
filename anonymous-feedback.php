<?php
/**
 * Plugin Name: Anonymous Feedback
 * Description: Shortcode [anonymous_feedback] that opens a popup for submitting anonymous feedback via email.
 * Version: 1.0.0
 * Author: GrowthRocket
 * Text Domain: anonymous-feedback
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Anonymous_Feedback_Plugin {

    private const RECIPIENT = 'info@growthrocket.fi';
    private const NONCE_ACTION = 'anon_feedback_submit';

    public function __construct() {
        add_shortcode( 'anonymous_feedback', [ $this, 'render_shortcode' ] );
        add_action( 'wp_ajax_anon_feedback_send', [ $this, 'handle_submit' ] );
        add_action( 'wp_ajax_nopriv_anon_feedback_send', [ $this, 'handle_submit' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue inline styles and scripts only when shortcode is present.
     */
    public function enqueue_assets() {
        // Assets are printed inline via the shortcode output, no external files needed.
    }

    /**
     * Render the shortcode button and popup markup.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'button_text' => 'Anna palautetta',
            'icon'        => '',
        ], $atts, 'anonymous_feedback' );

        $nonce = wp_create_nonce( self::NONCE_ACTION );
        $ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );
        $button_text = esc_html( $atts['button_text'] );

        // Determine icon URL: support attachment ID or direct URL
        $icon_url = '';
        if ( ! empty( $atts['icon'] ) ) {
            if ( is_numeric( $atts['icon'] ) ) {
                $icon_url = wp_get_attachment_image_url( intval( $atts['icon'] ), 'thumbnail' );
            } else {
                $icon_url = esc_url( $atts['icon'] );
            }
        }

        ob_start();
        ?>
        <style>
            .anon-fb-trigger {
                cursor: pointer;
                padding: 10px 24px;
                background: #0073aa;
                color: #fff;
                border: none;
                border-radius: 4px;
                font-size: 16px;
            }
            .anon-fb-trigger:hover {
                background: #005a87;
            }
            .anon-fb-trigger.has-icon {
                padding: 0;
                background: transparent;
                line-height: 0;
            }
            .anon-fb-trigger.has-icon:hover {
                background: transparent;
            }
            .anon-fb-trigger.has-icon img {
                width: 24px;
                height: 24px;
                object-fit: contain;
            }
            .anon-fb-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999999;
                justify-content: center;
                align-items: center;
            }
            .anon-fb-overlay.active {
                display: flex;
            }
            .anon-fb-popup {
                background: #fff;
                border-radius: 8px;
                padding: 32px;
                width: 90%;
                max-width: 480px;
                position: relative;
                box-shadow: 0 4px 24px rgba(0,0,0,0.2);
            }
            .anon-fb-popup h2 {
                margin: 0 0 8px;
                font-size: 20px;
            }
            .anon-fb-popup p.anon-fb-desc {
                margin: 0 0 20px;
                color: #555;
                font-size: 14px;
            }
            .anon-fb-popup label {
                display: block;
                font-weight: 600;
                margin-bottom: 6px;
                font-size: 14px;
            }
            .anon-fb-popup textarea {
                width: 100%;
                min-height: 120px;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 14px;
                resize: vertical;
                box-sizing: border-box;
            }
            .anon-fb-popup textarea:focus {
                outline: none;
                border-color: #0073aa;
            }
            .anon-fb-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 16px;
            }
            .anon-fb-actions button {
                padding: 8px 20px;
                border: none;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
            }
            .anon-fb-cancel {
                background: #ddd;
                color: #333;
            }
            .anon-fb-cancel:hover {
                background: #ccc;
            }
            .anon-fb-send {
                background: #0073aa;
                color: #fff;
            }
            .anon-fb-send:hover {
                background: #005a87;
            }
            .anon-fb-send:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            .anon-fb-close {
                position: absolute;
                top: 12px;
                right: 16px;
                background: none;
                border: none;
                font-size: 22px;
                cursor: pointer;
                color: #999;
                line-height: 1;
            }
            .anon-fb-close:hover {
                color: #333;
            }
            .anon-fb-notice {
                margin-top: 12px;
                padding: 10px;
                border-radius: 4px;
                font-size: 14px;
                display: none;
            }
            .anon-fb-notice.success {
                background: #d4edda;
                color: #155724;
                display: block;
            }
            .anon-fb-notice.error {
                background: #f8d7da;
                color: #721c24;
                display: block;
            }
        </style>

        <?php if ( $icon_url ) : ?>
        <button class="anon-fb-trigger has-icon" type="button" aria-label="<?php echo $button_text; ?>">
            <img src="<?php echo esc_url( $icon_url ); ?>" alt="">
        </button>
        <?php else : ?>
        <button class="anon-fb-trigger" type="button"><?php echo $button_text; ?></button>
        <?php endif; ?>

        <div class="anon-fb-overlay">
            <div class="anon-fb-popup">
                <button class="anon-fb-close" type="button" aria-label="Sulje">&times;</button>
                <h2>Anonyymi palaute</h2>
                <p class="anon-fb-desc">Palautteesi on täysin anonyymi. Emme kerää mitään henkilötietoja.</p>
                <form class="anon-fb-form">
                    <label for="anon-fb-message">Palautteesi</label>
                    <textarea id="anon-fb-message" name="message" placeholder="Kirjoita palautteesi tähän..." required></textarea>
                    <div class="anon-fb-notice"></div>
                    <div class="anon-fb-actions">
                        <button type="button" class="anon-fb-cancel">Peruuta</button>
                        <button type="submit" class="anon-fb-send">Lähetä</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        (function () {
            var wrapper   = document.currentScript.closest('div') || document.currentScript.parentNode;
            var trigger   = wrapper.querySelector('.anon-fb-trigger');
            var overlay   = wrapper.querySelector('.anon-fb-overlay');
            var closeBtn  = wrapper.querySelector('.anon-fb-close');
            var cancelBtn = wrapper.querySelector('.anon-fb-cancel');
            var form      = wrapper.querySelector('.anon-fb-form');
            var textarea  = wrapper.querySelector('#anon-fb-message');
            var notice    = wrapper.querySelector('.anon-fb-notice');
            var sendBtn   = wrapper.querySelector('.anon-fb-send');

            function openPopup() {
                overlay.classList.add('active');
                textarea.focus();
            }
            function closePopup() {
                overlay.classList.remove('active');
                form.reset();
                notice.className = 'anon-fb-notice';
                notice.textContent = '';
            }

            trigger.addEventListener('click', openPopup);
            closeBtn.addEventListener('click', closePopup);
            cancelBtn.addEventListener('click', closePopup);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closePopup();
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var message = textarea.value.trim();
                if (!message) return;

                sendBtn.disabled = true;
                sendBtn.textContent = 'Lähetetään...';
                notice.className = 'anon-fb-notice';
                notice.textContent = '';

                var data = new FormData();
                data.append('action', 'anon_feedback_send');
                data.append('nonce', '<?php echo esc_js( $nonce ); ?>');
                data.append('message', message);
                data.append('page_url', window.location.href);

                fetch('<?php echo $ajax_url; ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        notice.className = 'anon-fb-notice success';
                        notice.textContent = res.data;
                        form.reset();
                        setTimeout(closePopup, 2000);
                    } else {
                        notice.className = 'anon-fb-notice error';
                        notice.textContent = res.data;
                    }
                })
                .catch(function () {
                    notice.className = 'anon-fb-notice error';
                    notice.textContent = 'Jokin meni pieleen. Yritä uudelleen.';
                })
                .finally(function () {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Lähetä';
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle the AJAX form submission.
     */
    public function handle_submit() {
        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error( 'Turvatarkistus epäonnistui. Lataa sivu uudelleen ja yritä uudelleen.' );
        }

        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        if ( empty( $message ) ) {
            wp_send_json_error( 'Kirjoita palautteesi.' );
        }

        $page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

        $subject = 'Uusi anonyymi palaute';

        $body  = "Anonyymi palaute vastaanotettu:\n\n";
        $body .= $message . "\n\n";
        $body .= "---\n";
        $body .= 'Lähetetty sivulta: ' . $page_url . "\n";
        $body .= 'Päivämäärä: ' . wp_date( 'Y-m-d H:i:s' ) . "\n";

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        $sent = wp_mail( self::RECIPIENT, $subject, $body, $headers );

        if ( $sent ) {
            wp_send_json_success( 'Kiitos! Palautteesi on lähetetty.' );
        } else {
            wp_send_json_error( 'Palautteen lähetys epäonnistui. Yritä myöhemmin uudelleen.' );
        }
    }
}

new Anonymous_Feedback_Plugin();
