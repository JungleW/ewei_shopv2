<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Brand_EweiShopV2Page extends WebPage 
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
			$condition .= ' and brand like :keyword';
			$params[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}
		$brand = pdo_fetchall('SELECT id,uniacid,brand,description,status,displayorder FROM ' . tablename('ewei_shop_goods_brand') . "\n" . '                WHERE uniacid=:uniacid ' . $condition . ' order by id limit ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		$total = pdo_fetchcolumn('SELECT count(1) FROM ' . tablename('ewei_shop_goods_brand') . ' WHERE uniacid=:uniacid ' . $condition, $params);
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
			$item = pdo_fetch('SELECT id,uniacid,brand,description,status,displayorder FROM ' . tablename('ewei_shop_goods_brand') . "\n" . '                    WHERE id=:id and uniacid=:uniacid limit 1 ', array(':id' => $id, ':uniacid' => $uniacid));
		}
		if ($_W['ispost']) 
		{
			$data = array('brand' => trim($_GPC['brand']), 'description' => trim($_GPC['description']), 'status' => intval($_GPC['status']));
			if (!(empty($item))) 
			{
				pdo_update('ewei_shop_goods_brand', $data, array('id' => $item['id']));
				plog('goods.brand.edit', '修改品牌 ID: ' . $id);
			}
			else 
			{
				$data['uniacid'] = $uniacid;
				pdo_insert('ewei_shop_goods_brand', $data);
				$id = pdo_insertid();
				plog('goods.brand.add', '修改品牌 ID: ' . $id);
			}
			show_json(1, array('url' => webUrl('goods/brand/edit', array('id' => $id))));
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
		$items = pdo_fetchall('SELECT id,brand FROM ' . tablename('ewei_shop_goods_brand') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		if (empty($item)) 
		{
			$item = array();
		}
		foreach ($items as $item ) 
		{
			pdo_delete('ewei_shop_goods_brand', array('id' => $item['id']));
			plog('goods.edit', '从回收站彻底删除品牌<br/>ID: ' . $item['id'] . '<br/>品牌名称: ' . $item['brand']);
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
		$items = pdo_fetchall('SELECT id,brand FROM ' . tablename('ewei_shop_goods_brand') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		if (empty($item)) 
		{
			$item = array();
		}
		foreach ($items as $item ) 
		{
			pdo_update('ewei_shop_goods_brand', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
			plog('goods.brand.edit', (('修改品牌状态<br/>ID: ' . $item['id'] . '<br/>品牌名称: ' . $item['brand'] . '<br/>状态: ' . $_GPC['status']) == 1 ? '上架' : '下架'));
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
			$condition .= ' AND brand LIKE :keywords ';
			$params[':keywords'] = '%' . $kwd . '%';
		}
		$brands = pdo_fetchall('SELECT id,brand,description FROM ' . tablename('ewei_shop_goods_brand') . ' WHERE 1 ' . $condition . ' order by id desc', $params);
		if (empty($brands)) 
		{
			$brands = array();
		}
		include $this->template();
	}
	public function brandfile() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			show_json(0, '您查找的品牌不存在或已删除！');
		}
		$params = array(':uniacid' => $_W['uniacid'], ':id' => $id, ':status' => 1);
		$condition = ' and id = :id and uniacid=:uniacid and status = :status ';
		$brands = pdo_fetch('SELECT id,brand,description FROM ' . tablename('ewei_shop_goods_brand') . ' WHERE 1 ' . $condition . ' order by id desc', $params);
		if (empty($brands)) 
		{
			$brands = array();
			show_json(0, '您查找的品牌不存在或已删除！');
		}
		show_json(1, array('brand' => $brands['description']));
	}
}
?>