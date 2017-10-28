<?php
if (!(defined('IN_IA')))
{
	exit('Access Denied');
}
class Usercity_EweiShopV2Page extends WebPage
{
	public function main() 
	{

		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$params[':uniacid'] = $uniacid;
		$cityid = $_GET['cityid'];
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
		
		$_W['page']['title'] = '账号管理员列表';
		$account = pdo_fetch("SELECT * FROM ".tablename('uni_account')." WHERE uniacid = :uniacid", array(':uniacid' => $uniacid));
		if (empty($account)) {
			message('抱歉，您操作的公众号不存在或是已经被删除！');
		}
		$permission = pdo_fetchall("SELECT id, uid, role FROM ".tablename('uni_account_users')." WHERE uniacid = '$uniacid' and role != :role  ORDER BY uid ASC, role DESC", array(':role' => 'clerk'), 'uid');
		if (!empty($permission)) {
			$member = pdo_fetchall("SELECT u.username, au.uid FROM ".tablename('uni_account_users')." au left join ".tablename('users')." u on u.uid=au.uid WHERE au.cityid=0 and au.role='manager' and au.uid IN (".implode(',', array_keys($permission)).")", array(), 'uid');
		}
		$uids = array();
		foreach ($permission as $v) {
			$uids[] = $v['uid'];
		}
		
		$founders = explode(',', $_W['config']['setting']['founder']);
		
		include $this->template();
	}
	public function postcity()
	{
		global $_W;
		$id 	=$_POST['id'];
		$cityid =$_POST['cityId'];
		$uniacid = $_W['uniacid'];
		if(!empty($id) && !empty($cityid)){
			pdo_update('uni_account_users',array('cityid'=>0), array('cityid' => $cityid, 'uniacid' => $uniacid));
			$data=array('cityid'=>$cityid);
			 $bool=pdo_update('uni_account_users',$data,array('uid' => $id, 'uniacid' => $uniacid));
			 if($bool){
			 	echo  'success';
			 }else{
			 	echo 'fail';
			 }
		}else if(empty($id) && !empty($cityid)){
			$data=array('cityid'=>0);
			$bool=pdo_update('uni_account_users',$data,array('cityid' => $cityid, 'uniacid' => $uniacid));
			 if($bool){
			 	echo  'success';
			 }else{
			 	echo '';
			 }
		}
	}
}
?>