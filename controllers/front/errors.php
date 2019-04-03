<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Response;

class OnepilotErrorsModuleFrontController extends ModuleFrontController
{

    /** @var array */
    const LEVELS = array(
        1 => 'Info',
        2 => 'Warning',
        3 => 'Error',
        4 => 'Danger'
    );

    /** @var int */
    const PAGINATION = 20;
    /** @var int */
    private $perPage;
    /** @var string date*/
    private $from;
    /** @var string date*/
    private $to;
    /** @var string */
    private $levels = array();
    /** @var string */
    private $search;
    /** @var int */
    private $page;


    public function init()
    {
        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        parent::init();
        $this->perPage = Tools::getValue('per_page', self::PAGINATION);
        $this->from = Tools::getValue('from');
        $this->to = Tools::getValue('to');
        $this->levels = is_array($this->levels = Tools::getValue('levels')) ? $this->levels : null;
        $this->search = Tools::getValue('search');
        $this->page = Tools::getValue('page', 1);

        $sqlTotal = new \DbQuery();
        $sqlTotal->select('COUNT(*) as counter ');
        $sqlTotal->from('log');
        $this->applyFilters($sqlTotal);
        $total = (int)\Db::getInstance()->executeS($sqlTotal)[0]['counter'];

        $sql = new \DbQuery();
        $sql->select('id_log as id, severity as level, message, date_add as date');
        $sql->from('log', 'l');
        $this->applyFilters($sql);
        $sql->limit($this::PAGINATION, (($this->page - 1) * $this->perPage));
        $sql->orderBy('date_add desc');
        $results = \Db::getInstance()->executeS($sql);

        foreach ($results as &$result) {
            $index = $result['level'];
            $result['level'] = self::LEVELS[$index];
        }

        Response::make([
            'data' => $results,
            'current_page' => $this->page,
            'from' => (($this->page - 1) * $this->perPage) + 1,
            'last_page' => (int)ceil($total / $this->perPage),
            'per_page' => $this->perPage,
            'to' => (($this->page - 1) * $this->perPage) + $this->perPage,
            'total' => $total,
        ]);
    }

    private function applyFilters($sql)
    {
        if ($this->from) {
            $dateFrom = date('Y-m-d H:i:s', strtotime($this->from));
            $sql->where("date_add > '$dateFrom'");
        }

        if ($this->to) {
            $dateTo = date('Y-m-d H:i:s', strtotime($this->to));
            $sql->where("date_add < '$dateTo'");
        }

        if ($this->levels) {
            //do a method
            $levelsIds = $this->formatTextLevels();

            $sql->where("severity in (" . implode(',', $levelsIds) . ")");
        }

        if ($this->search) {
            $sql->where("message like '%$this->search%'");
        }
    }
    private function formatTextLevels(){
        foreach ($this->levels as $level) {
            $index = array_search($level, self::LEVELS);
            if ($index != false) {
                $levelsIds[] = $index;
            }
        }

        return $levelsIds;
    }

}