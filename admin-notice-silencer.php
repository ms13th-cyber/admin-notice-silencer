<?php
/**
 * Plugin Name:  Admin Notice Silencer
 * Plugin URI:   https://github.com/ms13th-cyber/admin-notice-silencer
 * Description:  管理画面の邪魔な通知（Admin Notice）をワンクリックで永続的にミュートし、設定画面からいつでも管理・解除できる軽量プラグインです。
 * Version:      1.0.0
 * Tested up to: 6.9.4
 * Requires PHP: 8.3.23
 * Author:       masato shibuya (Image-box Co., Ltd.)
 * Author URI:   https://github.com/ms13th-cyber
 * License:      GPL-2.0+
 * Text Domain:  admin-notice-silencer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Notice_Silencer {

	private $meta_key = 'ans_silenced_classes';

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_head', array( $this, 'inject_styles' ) );
			add_action( 'admin_footer', array( $this, 'inject_scripts' ) );
			add_action( 'wp_ajax_ans_silence_notice', array( $this, 'ajax_silence_notice' ) );
		}
	}

	/**
	 * ミュートされたクラスを隠すCSSと、ボタンの立体デザインを注入
	 */
	public function inject_styles() {
		$silenced_classes = get_user_meta( get_current_user_id(), $this->meta_key, true );
		if ( ! is_array( $silenced_classes ) || empty( $silenced_classes ) ) {
			$silenced_classes = array();
		}

		echo '<style id="ans-styles">';
		foreach ( $silenced_classes as $class ) {
			// 安全弁：致命的なエラーや更新通知は絶対に巻き添えにしない
			printf( '.notice.%1$s:not(.notice-error):not(.update-nag) { display: none !important; } ', esc_attr( $class ) );
		}
		?>
		/* すべての対象通知の親要素を、絶対配置の基準にする */
		.notice {
			position: relative !important;
		}

		/* ボタンが「テキストボタン」になるため、右側の余白を広めに確保 */
		.notice.is-dismissible {
			padding-right: 115px !important;
		}

		/* カチッと押せる四角いボタンのデザイン */
		.ans-silence-btn {
			position: absolute !important;
			top: 10px !important;
			right: 46px !important;
			background: #f6f7f7 !important;
			border: 1px solid #8c8f94 !important;
			color: #2c3338 !important;
			cursor: pointer;
			padding: 4px 8px;
			font-size: 11px;
			line-height: 1;
			border-radius: 3px;
			box-shadow: 0 1px 0 #ccc;
			transition: all 0.1s ease-in-out;
			z-index: 10;
		}
		.ans-silence-btn:hover {
			background: #f0f0f1 !important;
			border-color: #0a4b78 !important;
			color: #0a4b78 !important;
		}
		.ans-silence-btn:focus {
			outline: none;
			box-shadow: none;
		}
		</style>
		<?php
	}

	/**
	 * 通知にボタンを設置し、クリックでAjax送信するJS（安全弁付き）
	 */
	public function inject_scripts() {
		?>
		<script id="ans-scripts">
		document.addEventListener('DOMContentLoaded', function() {
			// 安全弁：致命的なエラー(.notice-error)やコアの更新通知(.update-nag)にはボタンを設置しない
			const notices = document.querySelectorAll('.notice:not(.notice-error):not(.update-nag)');

			notices.forEach(function(notice) {
				let targetClass = '';
				notice.classList.forEach(function(cls) {
					if (cls !== 'notice' && cls !== 'is-dismissible' && !cls.startsWith('notice-')) {
						targetClass = cls;
					}
				});

				if (!targetClass) {
					const text = notice.innerText.trim().substring(0, 50);
					targetClass = 'ans-hash-' + btoa(unescape(encodeURIComponent(text))).replace(/[^a-zA-Z0-9]/g, '').substring(0, 12);
					notice.classList.add(targetClass);
				}

				const silenceBtn = document.createElement('button');
				silenceBtn.className = 'ans-silence-btn';
				silenceBtn.setAttribute('type', 'button');
				silenceBtn.setAttribute('title', 'このプラグインの通知を永続的にミュート');
				silenceBtn.innerText = 'ミュート';

				notice.appendChild(silenceBtn);

				silenceBtn.addEventListener('click', function(e) {
					e.preventDefault();

					notice.style.transition = 'opacity 0.2s ease';
					notice.style.opacity = '0';
					setTimeout(function() { notice.style.display = 'none'; }, 200);

					const formData = new FormData();
					formData.append('action', 'ans_silence_notice');
					formData.append('target_class', targetClass);
					formData.append('nonce', '<?php echo wp_create_nonce( "ans_silence_nonce" ); ?>');

					fetch(ajaxurl, { method: 'POST', body: formData });
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Ajax：ミュート対象のクラス名をユーザーメタに保存
	 */
	public function ajax_silence_notice() {
		check_ajax_referer( 'ans_silence_nonce', 'nonce' );
		if ( ! current_user_can( 'read' ) ) { wp_send_json_error(); }

		$class = isset( $_POST['target_class'] ) ? sanitize_text_field( wp_unslash( $_POST['target_class'] ) ) : '';

		if ( ! empty( $class ) ) {
			$user_id = get_current_user_id();
			$silenced = get_user_meta( $user_id, $this->meta_key, true );
			if ( ! is_array( $silenced ) ) { $silenced = array(); }

			if ( ! in_array( $class, $silenced, true ) ) {
				$silenced[] = $class;
				update_user_meta( $user_id, $this->meta_key, $silenced );
			}
		}
		wp_send_json_success();
	}

	/**
	 * 設定ページの登録
	 */
	public function add_settings_page() {
		add_options_page(
			'Admin Notice Silencer',
			'Notice Silencer',
			'read',
			'admin-notice-silencer',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		if ( isset( $_POST['ans_action'] ) && $_POST['ans_action'] === 'unsilence' ) {
			check_admin_referer( 'ans_unsilence_nonce' );

			$target = isset( $_POST['unsilence_class'] ) ? sanitize_text_field( wp_unslash( $_POST['unsilence_class'] ) ) : '';
			$user_id = get_current_user_id();
			$silenced = get_user_meta( $user_id, $this->meta_key, true );

			if ( is_array( $silenced ) && ( $key = array_search( $target, $silenced, true ) ) !== false ) {
				unset( $silenced[ $key ] );
				update_user_meta( $user_id, $this->meta_key, array_values( $silenced ) );
				wp_safe_redirect( add_query_arg( 'settings-updated', 'true', wp_get_referer() ) );
				exit;
			}
		}
	}

	/**
	 * 設定画面のレンダリング
	 */
	public function render_settings_page() {
		$silenced = get_user_meta( get_current_user_id(), $this->meta_key, true );
		?>
		<div class="wrap">
			<h1>Admin Notice Silencer</h1>
			<p>現在あなたがミュートしている通知（プラグイン・識別子）の一覧です。解除すると再び表示されるようになります。</p>

			<?php if ( empty( $silenced ) ) : ?>
				<div class="card">
					<p>現在ミュートしている通知はありません。静かで快適な環境です！</p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped" style="max-width: 600px; margin-top: 20px;">
					<thead>
						<tr>
							<th style="padding: 10px;">識別クラス / ID</th>
							<th style="width: 100px; text-align: center; padding: 10px;">操作</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $silenced as $class ) : ?>
							<tr>
								<td style="vertical-align: middle; font-weight: bold; color: #23282d;">
									<code><?php echo esc_html( $class ); ?></code>
								</td>
								<td style="text-align: center;">
									<form method="post" action="">
										<?php wp_nonce_field( 'ans_unsilence_nonce' ); ?>
										<input type="hidden" name="ans_action" value="unsilence">
										<input type="hidden" name="unsilence_class" value="<?php echo esc_attr( $class ); ?>">
										<button type="submit" class="button button-small" title="ミュートを解除して再表示する">解除</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

new Admin_Notice_Silencer();


require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/ms13th-cyber/admin-notice-silencer/',
    __FILE__,
    'admin-notice-silencer'
);

$updateChecker->setBranch('main');