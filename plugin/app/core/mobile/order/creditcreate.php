<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}
 
require EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';
class Creditcreate_EweiShopV2Page extends AppMobilePage
{

	public function main()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$openid = $_W['openid'];
		$shop_name=$_W['shopset']['shop']['name'];

		if (empty($openid)) {
			app_error(AppError::$UserNotLogin);
		}
		$goodsid = $_GPC['id'];
		$sql = 'select * from ' . tablename('ewei_shop_creditshop_goods') . "  where id = " . $goodsid . ' and uniacid = ' . $uniacid . ' ';
		$goods = pdo_fetch($sql);

		$thumbs = array();
		if (!empty($goods['thumb'])) {
		  $thumbs = array($goods['thumb']);
		}
		$goods['thumbs'] = set_medias($thumbs);
		$order = array(
			'goods' => $goods,
			'total' => 1,
			'dispatchtype' => 0,
			'dispatchid' => $goods['dispatchid'],
			'shopname'=> $shop_name
		);

		//获取地址 
		$address = pdo_fetch('select * from ' . tablename('ewei_shop_member_address') . ' where openid=:openid and deleted=0 and isdefault=1  and uniacid=:uniacid limit 1', array(':uniacid' => $uniacid, ':openid' => $openid));

		$token = md5(microtime());
		$_SESSION['order_token'] = $token;
		
		$result = array(
			'member'             => array('realname' => $member['realname'], 'mobile' => $member['carrier_mobile']),
			'address'            => $address,
			'order'              => $order
			);
		app_json($result);
	}


	public function submit()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];

		$addressid = intval($_GPC['addressid']);

		$goodsid = $_GPC['goodsid'];
		$amount = $_GPC['total'];
		$remark = trim($_GPC['remark']); //买家备注

		$member = m('member')->getMember($openid);

		if ($member['isblack'] == 1) {
			app_error(AppError::$UserIsBlack);
		}

		// 获取地址信息
		$level = m('member')->getLevel($openid);
		$address = false;
		if (!empty($addressid) && ($dispatchtype == 0)) {
			$address = pdo_fetch('select * from ' . tablename('ewei_shop_member_address') . ' where id=:id and openid=:openid and uniacid=:uniacid   limit 1', array(':uniacid' => $uniacid, ':openid' => $openid, ':id' => $addressid));

			if (empty($address)) {
				app_error(AppError::$AddressNotFound);
			}
		}

		// 生成订单

		$sql = 'SELECT * ' . ' FROM ' . tablename('ewei_shop_creditshop_goods') . ' where id=:id and uniacid=:uniacid  limit 1';
		$data = pdo_fetch($sql, array(':uniacid' => $uniacid, ':id' => $goodsid));

		if (empty($data['status']) || !empty($data['deleted'])) {
			app_error(AppError::$GoodsNotFound, $data['title'] . '已下架!');
		}

		//计算总消耗积分
		$totalcredit = $data['credit'] * $amount;

		if($totalcredit > $member['credit1']){
			app_error(AppError::$NoEnoughCredit);
		}
		$totalprice = $data['price'] * $amount;
		$goodsprice = $data['marketprice'];
		$isverify = $data['isverify'];
		$dispatchid = $data['dispatchid'];
		$dispatchtype = $data['dispatchtype'];
		$credit = $data['credit'];
		$ordersn = m('common')->createNO('order', 'ordersn', 'DH');

		$order = array();
		$order['ismerch'] = 0;
		$order['parentid'] = 0;
		$order['uniacid'] = $uniacid;
		$order['openid'] = $openid;
		$order['ordersn'] = $ordersn;
		$order['price'] = $totalcredit;
		$order['oldprice'] = $goodsprice;
		$order['taskdiscountprice'] = 0;
		$order['discountprice'] = 0;
		$order['isdiscountprice'] = 0;
		$order['merchisdiscountprice'] = 0;
		$order['cash'] = 0;
		$order['status'] = 1;
		$order['remark'] = $remark;
		$order['addressid'] = empty($dispatchtype) ? $addressid : 0;
		$order['goodsprice'] = $goodsprice;
		$order['dispatchprice'] = $dispatch_price;
		$order['dispatchtype'] = $dispatchtype;
		$order['dispatchid'] = $dispatchid;
		$order['storeid'] = $carrierid;
		$order['carrier'] = $carriers;
		$order['createtime'] = time();
		$order['olddispatchprice'] = $dispatch_price;
		$order['couponid'] = 0;
		$order['couponmerchid'] = 0;
		$order['paytype'] = 3;
		$order['deductprice'] = 0;
		$order['deductcredit'] = 0;
		$order['deductcredit2'] = 0;
		$order['deductenough'] = 0;
		$order['merchdeductenough'] = 0;
		$order['couponprice'] = 0;
		$order['merchshow'] = 0;
		$order['buyagainprice'] = $goodsprice;
		$order['ispackage'] = 0;
		$order['packageid'] = 0;
		$author = p('author');

		if ($author) {
			$author_set = $author->getSet();
			if (!empty($member['agentid']) && !empty($member['authorid'])) {
				$order['authorid'] = $member['authorid'];
			}

			if (!empty($author_set['selfbuy']) && !empty($member['isauthor']) && !empty($member['authorstatus'])) {
				$order['authorid'] = $member['id'];
			}
		}
		$order['isparent'] = 0;
		$order['transid'] = '';
		$order['isverify'] = $isverify;
		$order['verifytype'] = 2;
		$order['verifyinfo'] = '';
		$order['virtual'] = 0;
		$order['isvirtual'] = 0;
		$order['isvirtualsend'] = 1;
		$order['invoicename'] = '';
		if (!empty($address)) {
			$order['address'] = iserializer($address);
		}


		pdo_insert('ewei_shop_order', $order);
		$orderid = pdo_insertid();

		// 生成订单商品
		$order_goods = array();
		$order_goods['goodsid'] = $goodsid;
		$order_goods['orderid'] = $orderid;
		$order_goods['uniacid'] = $uniacid;
		$order_goods['price'] = $credit * $amount;
		$order_goods['total'] = $amount;
		$order_goods['createtime'] = time();
		$order_goods['openid'] = $openid;

		$order_goods['productsn'] = $goodsprice;
		$order_goods['realprice'] = $goodsprice;
		$order_goods['oldprice'] = $goodsprice;
		$order_goods['isdiscountprice'] = 0;
		$order_goods['openid'] = $openid;
		$order_goods['diyformid'] = 0;
		$order_goods['canbuyagain'] = 1;

		pdo_insert('ewei_shop_order_goods', $order_goods);

		// 更新商品数量
		pdo_update('ewei_shop_creditshop_goods', array('total' => $goods['total']-$amount), array('id' => $goods['goodsid']));
		$restcredit =  $member['credit1'] - $totalcredit;
		pdo_update('ewei_shop_member', array('credit1' => $restcredit), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));


		m('notice')->sendOrderMessage($orderid);
		com_run('printer::sendOrderMessage', $orderid);
		$pluginc = p('commission');

		if ($pluginc) {
			if ($multiple_order == 0) {
				$pluginc->checkOrderConfirm($orderid);
			}
			else {
				if (!empty($merch_array)) {
					foreach ($merch_array as $key => $value) {
						$pluginc->checkOrderConfirm($value['orderid']);
					}
				}
			}
		}

		unset($_SESSION[$openid . '_order_create']);
		app_json(array('orderid' => $orderid));
	}

}

?>
