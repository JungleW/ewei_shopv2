<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
} 
require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Credit_EweiShopV2Page extends AppMobilePage
{

	public function main(){
		global $_W;
		global $_GPC;
		$set = $_W['shopset'];

		$member = $this->member;
		
		//credit1为积分   credit2为可兑换金额
		$minCredit = 0;
		$credit1 = $member['credit1'];
		$credit2 = 0;
		$wechat = array('success' => false);
		$alipay = array('success' => false);

				
		if ($this->iswxapp) {
			
			if (!empty($set['pay']['wxapp'])) {
				$wechat['success'] = true;
			}
		}
		else {
			if (!empty($set['pay']['nativeapp_wechat'])) {
				
				$wechat['success'] = true;
			}

			if (!empty($set['pay']['nativeapp_alipay'])) {
				$alipay['success'] = true;
			}
		}
		
		$exchang=m('shop')->getExchange(true);
		
		if(!empty($exchang)){
			$minCredit = $exchang[0][0];
			$credit2 = round(intval(creditExchange($credit1, $exchang)*100, 0)/100, 2);
		}else{
			if(!empty($credit1)){
				app_json(array('credit1' => $credit1,'credit2' => 0,'minCredit' => 1));
			}else{
				app_json(array('credit1' => 0,'credit2' => 0,'minCredit' => 1));
			}
		}

		if ($_W['ispost']) {
			$credit1 = 0;
			$credit2 = $member['credit2'] + $credit2;
			pdo_update('ewei_shop_member', array('credit1' => $credit1, 'credit2' => $credit2), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
			$credit2 = 0;
			
		}

		app_json(array('credit1' => $credit1,'credit2' => $credit2,'minCredit' => $minCredit, 'wechat' => $wechat, 'alipay' => $alipay, 'coupons' => $this->getrecouponlist(), 'minimumcharge' => $minimumcharge));
	
	}
	
}