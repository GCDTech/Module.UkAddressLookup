<?php

namespace Gcd\UkAddressLookup;

use Rhubarb\Crown\Exceptions\RhubarbException;
use Rhubarb\Leaf\Presenters\Controls\Buttons\Button;
use Rhubarb\Leaf\Presenters\Controls\ControlPresenter;
use Rhubarb\Leaf\Presenters\Controls\ControlView;
use Rhubarb\Leaf\Presenters\Controls\Selection\DropDown\DropDown;
use Rhubarb\Leaf\Presenters\Controls\Text\TextBox\TextBox;
use Rhubarb\Leaf\Validation\HasValueClientSide;
use Rhubarb\Leaf\Validation\ValidatorClientSide;
use Rhubarb\Leaf\Views\Validation\Placeholder;

class AddressUkPafLookupView extends ControlView
{
    public function getDeploymentPackage()
    {
        $package = parent::getDeploymentPackage();
        $package->resourcesToDeploy[] = __DIR__ . '/AddressUkPafLookupViewBridge.js';

        return $package;
    }

    protected function getClientSideViewBridgeName()
    {
        return 'AddressUkPafLookupViewBridge';
    }

    public function createPresenters()
    {
        /** @var ControlPresenter[] $pafFields */
        $pafFields = [];

        $this->addPresenters(
            $resultsDropDown = new DropDown('Results'),
            $country = new DropDown('Country'),
            $houseNumber = new TextBox('HouseNumber', 10),
            $postcodeSearch = new TextBox('PostcodeSearch', 15),
            $search = new Button('Search', 'Search', function () use ($resultsDropDown) {
                try {
                    $results = $this->raiseEvent('Search');
                } catch (RhubarbException $ex) {
                    return $ex->getPublicMessage();
                } catch (\Exception $ex) {
                    return $ex->getMessage();
                }

                if (!count($results)) {
                    return 'No addresses found for postcode';
                }

                if (isset($results['Type']) && $results['Type'] == 'NotAllowed') {
                    return 'Address lookup service unavailable';
                }

                array_walk($results, function (&$address) {
                    $address = [1, implode(', ', $address), $address];
                });

                array_unshift($results, ['', 'Select address...']);

                $resultsDropDown->setSelectionItems($results);
                $resultsDropDown->rePresent();

                return 'success';
            }, true),
            $pafField[] = $this->createPafInput('Organisation'),
            $pafField[] = $this->createPafInput('AddressLine1'),
            $pafField[] = $this->createPafInput('AddressLine2'),
            $pafField[] = $this->createPafInput('AddressLine3'),
            $pafField[] = $this->createPafInput('Town'),
            $pafField[] = $this->createPafInput('County'),
            $pafField[] = $this->createPafInput('Postcode')
        );

        foreach ($pafFields as $pafField) {
            $pafField->addCssClassName('paf-address-field');
        }

        $resultsDropDown->addCssClassName('-hidden');

        $validator = new ValidatorClientSide();
        $validator->validations[] = $validation = new HasValueClientSide('PostcodeSearch');
        $validation->failedMessageOverride = 'Please enter a postcode';
        $search->setValidator($validator);

        $countriesList = [];
        foreach (Country::getCountriesList() as $key => $value) {
            $countriesList[] = [$key, $value];
        }
        $country->setSelectionItems([['', 'Please select...'], $countriesList]);
        $postcodeSearch->setPlaceholderText('Postcode');
        $houseNumber->setPlaceholderText('No.');
    }

    protected function createPafInput($fieldName)
    {
        switch($fieldName){
            case 'Organisation':
            case 'AddressLine1':
                $size = 50;
                break;
            case 'County':
                $size = 20;
                break;
            case 'Postcode':
                $size = 10;
                break;
            default:
                $size = 30;
                break;
        }
        return new TextBox($fieldName, $size);
    }

    public function printViewContent()
    {
        $addressShown = $this->getData('AddressPopulated');

        if ($this->getData('IncludeCountry')) {
            $this->printFieldset("", ["Country"]);
        }
        ?>
        <div class="paf-search-fields<?= $addressShown ? ' -hidden' : '' ?>">
            <?php
            $this->printFieldset('', [
                'Find Address' => ($this->getData('IncludeHouseNumberSearch') ? $this->presenters['HouseNumber'] : '').
                    $this->presenters['PostcodeSearch'].(new Placeholder('PostcodeSearch', $this)).
                    $this->presenters['Search']
            ]);

            print $this->presenters['Results'];
            ?>
            <span class="paf-search-error -hidden"></span>
        </div>

        <?php
        $manualAddressEntryText = $this->getData('ManualAddressEntryText');
        if ($manualAddressEntryText) {
            ?>
            <p class="paf-manual-address-action<?= $addressShown ? ' -hidden' : '' ?>"><a class="paf-manual-address-link"><?= $manualAddressEntryText ?></a></p>
            <?php
        }
        ?>

        <div class="paf-address<?= $addressShown ? '' : ' -hidden' ?>">
            <p class="paf-search-again-action"><b><a class="paf-search-again-link">Search for address</a></b></p>

            <div class="paf-address-summary">
                <?php
                $this->printAddressSummary();
                ?>
            </div>
            <div class="paf-address-fields -hidden">
                <?php
                $this->printFieldset('', AddressUkPafLookup::$addressFields);
                ?>
            </div>
        </div>
        <?php
    }

    protected function printAddressSummary()
    {
        print '<a class="paf-change-address-button">Change</a>';
        foreach (AddressUkPafLookup::$addressFields as $field) {
            print '<span class="paf-address-part" data-paf-field="'.$field.'">'.$this->getData($field).'</span>';
        }
    }
}
