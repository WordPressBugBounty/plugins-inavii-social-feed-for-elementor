<?php

namespace Inavii\Instagram\Includes\Integration;

use Elementor\Plugin;

class WidgetsManager
{
    private $widgetClass = [];
    public function __construct()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return;
        }

        add_action('elementor/elements/categories_registered', array($this, 'addWidgetCategories'));

        if (version_compare(ELEMENTOR_VERSION, '3.5.0', '<')) {
            add_action('elementor/widgets/widgets_registered', array($this, 'registerWidgets'));
        }else{
            add_action('elementor/widgets/register', array($this, 'registerWidgets'));
        }

        $this->getActiveWidgets();
    }

    private function getActiveWidgets(): void
    {
        foreach (WidgetsLists::widgetsList() as $widget) {
            $this->widgetClass[] = 'Inavii\Instagram\Includes\Integration\Widgets\\'.str_replace('-', '_',
                    $widget['key']);
        }
    }

    public function addWidgetCategories($elementsManager): void
    {
        $elementsManager->add_category(
            'inavii-social-feed-e',
            [
                'title' => __('Inavii Social Feed', 'inavii-social-feed-e'),
                'icon'  => 'fa fa-plug',
            ]
        );
    }

    public function registerWidgets(): void
    {
        $widgetsManager = Plugin::instance()->widgets_manager;

        foreach ($this->widgetClass as $class) {
            if (class_exists($class)) {
                $widgetInstance = new $class;
                if (version_compare(ELEMENTOR_VERSION, '3.5.0', '<')) {
                    $widgetsManager->register_widget_type($widgetInstance);
                } else {
                    $widgetsManager->register($widgetInstance);
                }
            }
        }
    }
}
