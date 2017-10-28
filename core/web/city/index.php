<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_EweiShopV2Page extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$params[':uniacid'] = $uniacid;
		$condition = '';
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		if ($_GPC['enabled'] != '') 
		{
			$condition .= ' and status=' . intval($_GPC['enabled']);
		}
		if (!(empty($_GPC['keyword']))) 
		{
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition .= ' and city like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}
		$city = pdo_fetchall('SELECT c.id,c.uniacid,c.city,c.status,c.displayorder, u.uid, u.username FROM ' . tablename('ewei_shop_city') .' c left join '.tablename('uni_account_users').' ac on ac.cityid=c.id and ac.uniacid=:uniacid left join ' .tablename('users').' u on u.uid=ac.uid WHERE c.uniacid=:uniacid ' . $condition . ' order by id limit ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(1) FROM ' . tablename('ewei_shop_city') . ' WHERE uniacid=:uniacid ' . $condition, $params);
		$pager = pagination($total, $pindex, $psize);
		
		include $this->template();
	}
	public function add() 
	{
		$this->post();
	}
	public function edit() 
	{
		$this->post();
	}
	protected function post() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		if (!(empty($id))) 
		{
			$item = pdo_fetch('SELECT id,uniacid,city,description,status,displayorder FROM ' . tablename('ewei_shop_city') . "\n" . '                    WHERE id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $uniacid));
		}
		if ($_W['ispost']) 
		{
			$data = array('city' => trim($_GPC['city']), 'description' => trim($_GPC['description']), 'status' => intval($_GPC['status']));
			if (!(empty($item))) 
			{
				pdo_update('ewei_shop_city', $data, array('id' => $item['id']));
				plog('goods.city.edit', '修改城市 ID: ' . $id);
			}
			else 
			{
				$data['uniacid'] = $uniacid;
				pdo_insert('ewei_shop_city', $data);
				$id = pdo_insertid();
				plog('goods.city.add', '修改城市 ID: ' . $id);
			}
			show_json(1, array('url' => webUrl('city/edit', array('id' => $id))));
		}
		include $this->template();
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,city FROM ' . tablename('ewei_shop_city') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		if (empty($item)) 
		{
			$item = array();
		}
		foreach ($items as $item ) 
		{
			pdo_delete('ewei_shop_city', array('id' => $item['id']));
			plog('goods.edit', '从回收站彻底删除城市<br/>ID: ' . $item['id'] . '<br/>城市名称: ' . $item['city']);
		}
		show_json(1, array('url' => referer()));
	}
	public function status() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,city FROM ' . tablename('ewei_shop_city') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		if (empty($item)) 
		{
			$item = array();
		}
		foreach ($items as $item ) 
		{
			pdo_update('ewei_shop_city', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('goods.city.edit', (('修改城市状态<br/>ID: ' . $item['id'] . '<br/>城市名称: ' . $item['city'] . '<br/>状态: ' . $_GPC['status']) == 1 ? '上架' : '下架'));
		}
		show_json(1, array('url' => referer()));
	}
	public function query() 
	{
		global $_W;
		global $_GPC;
		$kwd = trim($_GPC['keyword']);
		$params = array();
		$params[':uniacid'] = $_W['uniacid'];
		$condition = ' and uniacid=:uniacid and status = 1 ';
		if (!(empty($kwd))) 
		{
			$condition .= ' AND city LIKE :keywords ';
			$params[':keywords'] = '%' . $kwd . '%';
		}
		$citys = pdo_fetchall('SELECT id,city,description FROM ' . tablename('ewei_shop_city') . ' WHERE 1 ' . $condition . ' order by id desc', $params);
		if (empty($citys)) 
		{
			$citys = array();
		}
		include $this->template();
	}
	public function cityfile() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			show_json(0, '您查找的城市不存在或已删除！');
		}
		$params = array(':uniacid' => $_W['uniacid'], ':id' => $id, ':status' => 1);
		$condition = ' and id = :id and uniacid=:uniacid and status = :status ';
		$citys = pdo_fetch('SELECT id,city,description FROM ' . tablename('ewei_shop_city') . ' WHERE 1 ' . $condition . ' order by id desc', $params);
		if (empty($citys)) 
		{
			$citys = array();
			show_json(0, '您查找的城市不存在或已删除！');
		}
		show_json(1, array('city' => $citys['description']));
	}
}
?>