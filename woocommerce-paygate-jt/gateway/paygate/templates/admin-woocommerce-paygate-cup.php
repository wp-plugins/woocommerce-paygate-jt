<h3><?php echo $this->method_title ;?></h3>

	<div id="wc_get_started" class="paygate">
		<span class="main"><?php  _e( 'PayGate 결제 시작하기' ); ?></span>
		<span>
			<a href="https://admin.paygate.net/" target="paygate_admin" >페이게이트 상점관리자 </a>
		</span>

		<p><a href="http://www.paygate.net/apply/general.php" target="_blank" class="button button-primary"><?php _e( 'Join', 'woocommerce' ); ?></a> </p>
	</div>
	<table class="form-table">
		<?php $this->generate_settings_html(); ?>
	</table><!--/.form-table-->