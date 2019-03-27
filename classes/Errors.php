<?php namespace OnePilot;

class Errors
{
    /** @var array intervals in minutes */
    const INTERVALS = [
        1 * 24 * 60,
        7 * 24 * 60,
        30 * 24 * 60,
    ];


    /**
     * Return the log activity of the last day,week,month by Level
     * @return array
     */
    public function overview()
    {
        $overview = [];
        foreach (self::INTERVALS as $interval) {
            $overview[$interval] = $this->last($interval);
        }

        return $overview;
    }

    private function last($minutes)
    {
        $dateToday = date('Y-m-d H:i:s');
        $time = strtotime($dateToday);
        $time = $time - $minutes*60;
        $dateToday = date('Y-m-d H:i:s' , $time);
        print_r($dateToday);
        $sql = new \DbQuery();
        $sql->select('severity,count(*) as count');
        $sql->from('log', 'l');
        $sql->where("date_add > NOW() - $dateToday");
        $sql->groupBy('severity');

        $results = \Db::getInstance()->executeS($sql);

        $logs = [];
        foreach ($results as $result) {
            $logs[$result['severity']] = $result['count'];
        }

        return $logs;
    }
}
