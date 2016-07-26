<?php namespace Octommerce\Octommerce\Controllers;

use Db;
use BackendMenu;
use Carbon\Carbon;
use Backend\Classes\Controller;
use Octommerce\Octommerce\Models\Order;
use Octommerce\Octommerce\Models\Product;
use Octommerce\Octommerce\ReportWidgets\Summary as SummaryWidget;

/**
 * Reports Back-end Controller
 */
class Reports extends Controller
{

    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    public $listConfig = ['products' => 'config_list.yaml'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Octommerce.Octommerce', 'commerce', 'reports');

        $summaryWidget = new SummaryWidget($this);
        $summaryWidget->alias = 'summary';
        $summaryWidget->bindToController();
    }

    public function index()
    {
        $this->asExtension('ListController')->index();
        $this->bodyClass = 'compact-container';

        $this->vars['dataPaidOrders'] = $this->getLastOrdersData();
    }

    protected function getLastOrdersData($status = 'paid', $duration = 30)
    {

        $date = Carbon::now()->subDays($duration);

        // lists() does not accept raw queries,
        // so you have to specify the SELECT clause
        $days = Order::select(array(
                Db::raw('DATE(`created_at`) as `date`'),
                Db::raw('SUM(subtotal) as `amount`')
            ))
            ->sales()
            ->where('created_at', '>', $date)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->lists('amount', 'date');

        $points = [];

        for ($i = $duration; $i > 0; $i--) {
           $date = Carbon::now()->subDays($i);

           $points[] = [
               $date->timestamp * 1000,
               isset($days[$date->format('Y-m-d')]) ? $days[$date->format('Y-m-d')] : 0,
           ];
        }

        // Parse format
        return str_replace('"', '', substr(substr(json_encode($points), 1), 0, -1));
    }

}