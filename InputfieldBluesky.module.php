<?php

/**
 * InputfieldBluesky
 * Custom inputfield to manage Bluesky settings
 */

namespace ProcessWire;

class InputfieldBluesky extends InputfieldWrapper {
    public static function getModuleInfo()
    {
        return [
            'title' => 'Bluesky',
            'version' => 1,
            'summary' => 'Contains Bluesky page configuration',
            'author' => 'TwoWheelDev',
            'icon' => 'plug',
        ];
    }

    public function ___render() {
        $output = '';

        // Bluesky Handle
        /** @var InputfieldText $handleField */
        $handleField = $this->modules->get('InputfieldText');
        $handleField->attr('name', $this->name . '[handle]');
        $handleField->label = 'Bluesky Handle';
        $handleField->attr('value', $this->value->get('bluesky_handle') ?? '');

        // Number of Posts
        /** @var InputfieldInteger $countField */
        $countField = $this->modules->get('InputfieldInteger');
        $countField->attr('name', $this->name . '[post_count]');
        $countField->label = 'Number of Posts';
        $countField->attr('value', $this->value->get('bluesky_post_count') ?? 5);

        // Include Reposts
        /** @var InputfieldCheckbox $repostsField */
        $repostsField = $this->modules->get('InputfieldCheckbox');
        $repostsField->attr('name', $this->name . '[include_reposts]');
        $repostsField->label = 'Include Reposts?';
        $repostsField->attr('checked', !empty($this->value->get('bluesky_include_reposts')));

        $output .= $handleField->render();
        $output .= $countField->render();
        $output .= $repostsField->render();

        return $output;
    }

    public function ___processInput(WireInputData $input) {
        $data = new WireData();
        $value = $input->{$this->name};
        bd($value);

        $data->bluesky_handle = $value['handle'] ?? '';
        $data->bluesky_post_count = (int) ($value['post_count'] ?? 5);
        $data->bluesky_include_reposts = isset($value['include_reposts']) && $value['bluesky_include_reposts'] ? true : false;

        $this->value = $data;
        $this->trackChange('value');
    }

    public function isEmpty() {
        return empty($this->value->bluesky_handle);
    }
}
