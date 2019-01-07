<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD Nederland B.V.
 *
 * Copyright (C) 2018  DPD Nederland B.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace BigFish\Shipping\Block\Adminhtml;

class TablerateImport extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    protected $shipconfig;

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }
    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {

        // get the group name
        $groupId = $this->getContainer()['group']['id'];

        $html = '';
        $html .= '<input id="time_condition_'.$groupId.'" type="hidden" name="' . $this->getName() . '" value="' . time() . '" />';
        $html .= <<<EndHTML
        <script>
        
        var groupId = '$groupId';
        
        require(['prototype'], function(){
        Event.observe($('carriers_' + groupId + '_condition_name'), 'change', checkConditionName.bind(this));
        function checkConditionName(event)
        {
            var conditionNameElement = Event.element(event);
            if (conditionNameElement && conditionNameElement.id) {
                $('time_condition_' + groupId).value = '_' + conditionNameElement.value + '/' + Math.random();
            }
        }
        });
        </script>
EndHTML;
        $html .= parent::getElementHtml();
        return $html;
    }
}
