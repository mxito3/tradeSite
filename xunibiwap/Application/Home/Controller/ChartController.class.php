<?php
namespace Home\Controller;

class ChartController extends HomeController
{
	public function getJsonData($market = NULL, $ajax = 'json')
	{
		if ($market) {
			$data = (APP_DEBUG ? null : S('ChartgetJsonData' . $market));

			if (!$data) {
				$data[0] = $this->getTradeBuy($market);
				$data[1] = $this->getTradeSell($market);
				$data[2] = $this->getTradeLog($market);
				S('ChartgetJsonData' . $market, $data);
			}

			exit(json_encode($data));
		}
	}

	protected function getTradeBuy($market)
	{
		$mo = M();
		$buy = $mo->query('select id,price,sum(num-deal)as nums from zhisucom_trade  where status=0 and type=1 and market =\'' . $market . '\' group by price order by price desc limit 100;');
		$data = '';

		if ($buy) {
			$maxNums = maxArrayKey($buy, 'nums') / 2;

			foreach ($buy as $k => $v) {
				$data .= '<dd class="clear"><span class="wb_50">' . floatval($v['price']) . '</span><span class="wb_50">' . floatval($v['nums']) . '</span><i class="turntable_bg_red" style="width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px"></i></dd>';
			}
		}

		return $data;
	}

	protected function getTradeSell($market)
	{
		$mo = M();
		$sell = $mo->query('select id,price,sum(num-deal)as nums from zhisucom_trade where status=0 and type=2 and market =\'' . $market . '\' group by price order by price asc limit 100;');
		$data = '';

		if ($sell) {
			$maxNums = maxArrayKey($sell, 'nums') / 2;

			foreach ($sell as $k => $v) {
				$data .= '<dd class="clear"><span class="wb_50 pl_20">' . floatval($v['price']) . '</span><span class="wb_50">' . floatval($v['nums']) . '</span><i class="turntable_bg_red turntable_bg_green" style="width: ' . ((($maxNums < $v['nums'] ? $maxNums : $v['nums']) / $maxNums) * 100) . 'px;" ></i></dd>';
			}
		}

		return $data;
	}

	protected function getTradeLog($market)
	{
		$log = M('TradeLog')->where(array('status' => 1, 'market' => $market))->order('id desc')->limit(100)->select();
		$data = '';

		if ($log) {
			foreach ($log as $k => $v) {
				if ($v['type'] == 1) {
					$type = 'buy';
				}
				else {
					$type = 'sell';
				}

				$data .= '<tr><td class="' . $type . '"  width="70">' . date('H:i:s', $v['addtime']) . '</td><td class="' . $type . '"  width="70">' . floatval($v['price']) . '</td><td class="' . $type . '"  width="100">' . floatval($v['num']) . '</td><td class="' . $type . '">' . floatval($v['mum']) . '</td></tr>';
			}
		}

		return $data;
	}

	public function trend()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$this->assign('market', $market);
		$this->display();
	}

	public function getMarketTrendJson()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$data = (APP_DEBUG ? null : S('ChartgetMarketTrendJson' . $market));

		if (!$data) {
			$data = M('TradeLog')->where(array(
				'market'  => $market,
				'addtime' => array('gt', time() - (60 * 60 * 24 * 30 * 2))
				))->select();
			S('ChartgetMarketTrendJson' . $market, $data);
		}

		foreach ($data as $k => $v) {
			$json_data[$k][0] = $v['addtime'];
			$json_data[$k][1] = $v['price'];
		}

		exit(json_encode($json_data));
	}

	public function ordinary()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$this->assign('market', $market);
		$this->display();
	}

	public function getMarketOrdinaryJson()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

		if (in_array($input['time'], $timearr)) {
			$time = $input['time'];
		}
		else {
			$time = 5;
		}

		$timeaa = (APP_DEBUG ? null : S('ChartgetMarketOrdinaryJsontime' . $market . $time));

		if (($timeaa + 60) < time()) {
			S('ChartgetMarketOrdinaryJson' . $market . $time, null);
			S('ChartgetMarketOrdinaryJsontime' . $market . $time, time());
		}

		$tradeJson = (APP_DEBUG ? null : S('ChartgetMarketOrdinaryJson' . $market . $time));

		if (!$tradeJson) {
			$tradeJson = M('TradeJson')->where(array(
				'market' => $market,
				'type'   => $time,
				'data'   => array('neq', '')
				))->order('id desc')->limit(100)->select();
			S('ChartgetMarketOrdinaryJson' . $market . $time, $tradeJson);
		}

		krsort($tradeJson);

		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}

		exit(json_encode($json_data));
	}

	public function specialty()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$this->assign('market', $market);
		$this->display();
	}

	public function getMarketSpecialtyJson()
	{
		// TODO: SEPARATE
		$input = I('get.');
		$market = (is_array(C('market')[$input['market']]) ? trim($input['market']) : C('market_mr'));
		$timearr = array(1, 3, 5, 10, 15, 30, 60, 120, 240, 360, 720, 1440, 10080);

		if (in_array($input['step'] / 60, $timearr)) {
			$time = $input['step'] / 60;
		}
		else {
			$time = 5;
		}

		$timeaa = (APP_DEBUG ? null : S('ChartgetMarketSpecialtyJsontime' . $market . $time));

		if (($timeaa + 60) < time()) {
			S('ChartgetMarketSpecialtyJson' . $market . $time, null);
			S('ChartgetMarketSpecialtyJsontime' . $market . $time, time());
		}

		$tradeJson = (APP_DEBUG ? null : S('ChartgetMarketSpecialtyJson' . $market . $time));

		if (!$tradeJson) {
			$tradeJson = M('TradeJson')->where(array('type' => $time, 'market' => $market))->order('id asc')->limit(1000)->select();
			S('ChartgetMarketSpecialtyJson' . $market . $time, $tradeJson);
		}

		foreach ($tradeJson as $k => $v) {
			$json_data[] = json_decode($v['data'], true);
		}

		foreach ($json_data as $k => $v) {
			$data[$k][0] = $v[0];
			$data[$k][1] = 0;
			$data[$k][2] = 0;
			$data[$k][3] = $v[2];
			$data[$k][4] = $v[5];
			$data[$k][5] = $v[3];
			$data[$k][6] = $v[4];
			$data[$k][7] = $v[1];
		}

		exit(json_encode($data));
	}

	public function getSpecialtyTrades()
	{
		$input = I('get.');

		if (!$input['since']) {
			$tradeLog = M('TradeLog')->where(array('market' => $input['market']))->order('id desc')->find();
			$json_data[] = array('tid' => $tradeLog['id'], 'date' => $tradeLog['addtime'], 'price' => $tradeLog['price'], 'amount' => $tradeLog['num'], 'trade_type' => $tradeLog['type'] == 1 ? 'bid' : 'ask');
			exit(json_encode($json_data));
		}
		else {
			$tradeLog = M('TradeLog')->where(array(
				'market' => $input['market'],
				'id'     => array('gt', $input['since'])
				))->order('id desc')->select();

			foreach ($tradeLog as $k => $v) {
				$json_data[] = array('tid' => $v['id'], 'date' => $v['addtime'], 'price' => $v['price'], 'amount' => $v['num'], 'trade_type' => $v['type'] == 1 ? 'bid' : 'ask');
			}

			exit(json_encode($json_data));
		}
	}
}

?>