<?php
#App\Plugins\Extensions\Payment\Paypal\AppConfig.php
namespace App\Plugins\Extensions\Payment\Po;

use App\Models\AdminConfig;
use App\Models\ShopOrderStatus;
use App\Plugins\Extensions\ConfigDefault;
class AppConfig extends ConfigDefault
{

    public $configGroup = 'Extensions';
    public $configCode = 'Payment';
    public $configKey = 'PO';
    public $pathPlugin;
    public const ORDER_STATUS_FAILD = 6;
    public const ORDER_STATUS_SUCCESS = 5;

    public function __construct()
    {
        $this->pathPlugin = $this->configGroup . '/' . $this->configCode . '/' . $this->configKey;
        $this->title = trans($this->pathPlugin.'::'.$this->configKey . '.title');
        $this->image = 'images/' . $this->pathPlugin . '.png';
        $this->version = '2.1';
        $this->auth = 'Naruto';
        $this->link = 'https://s-cart.org';
    }

    public function install()
    {
        $return = ['error' => 0, 'msg' => ''];
        $check = AdminConfig::where('key', $this->configKey)->first();
        if ($check) {
            $return = ['error' => 1, 'msg' => 'Module exist'];
        } else {

            $configPo = [
                [
                    'group' => $this->configGroup,
                    'code' => $this->configCode,
                    'key' => $this->configKey,
                    'sort' => 0, // Sort extensions in group
                    'value' => self::ON, //1- Enable extension; 0 - Disable
                    'detail' => $this->pathPlugin.'::'.$this->configKey . '.title',
                ],
                [
                    'group' => '',
                    'code' => 'po_config',
                    'key' => 'po_currency',
                    'sort' => 0, // Sort extensions in group
                    'value' => 'USD',
                    'detail' => $this->pathPlugin.'::'.$this->configKey . '.po_currency',
                ],
                [
                    'group' => '',
                    'code' => 'po_config',
                    'key' => 'po_order_status_success',
                    'sort' => 0, // Sort extensions in group
                    'value' => self::ORDER_STATUS_SUCCESS,
                    'detail' => $this->pathPlugin.'::'.$this->configKey . '.po_order_status_success',
                ],
                [
                    'group' => '',
                    'code' => 'po_config',
                    'key' => 'po_order_status_faild',
                    'sort' => 0, // Sort extensions in group
                    'value' => self::ORDER_STATUS_FAILD,
                    'detail' => $this->pathPlugin.'::'.$this->configKey . '.po_order_status_faild',
                ],
            ];
            $process = AdminConfig::insert(
                $configPo
            );
            if (!$process) {
                $return = ['error' => 1, 'msg' => 'Error when install'];
            }
        }
        return $return;
    }

    public function uninstall()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)->where('key', $this->configKey)->delete();
        $process2 = (new AdminConfig)->whereIn('key', ['po_currency', 'po_order_status_success', 'po_order_status_faild'])->delete();
        if (!$process & !$process2) {
            $return = ['error' => 1, 'msg' => 'Error when uninstall'];
        }
        return $return;
    }
    public function enable()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)->where('key', $this->configKey)->update(['value' => self::ON]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error enable'];
        }
        return $return;
    }
    public function disable()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)->where('key', $this->configKey)->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error disable'];
        }
        return $return;
    }

    public function config()
    {
        return view($this->pathPlugin . '::' . $this->configKey)->with(
            [
                'group' => $this->configCode,
                'key' => $this->configKey,
                'title' => $this->title,
                'jsonStatusOrder' => json_encode(ShopOrderStatus::mapValue()),
            ]);
    }

    public function process($data)
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = AdminConfig::where('key', $data['pk'])->update(['value' => $data['value']]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => 'Error update'];
        }
        return $return;
    }

    public function getData()
    {
        $arrData = [
            'title' => $this->title,
            'code' => $this->configKey,
            'image' => $this->image,
            'permission' => self::ALLOW,
            'version' => $this->version,
            'auth' => $this->auth,
            'link' => $this->link,
        ];
        return $arrData;
    }


}
