<?php
declare(strict_types=1);

namespace Tests\Brille24\CustomerOptionsPlugin\Behat\Page\Product;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Brille24\CustomerOptionsPlugin\Entity\CustomerOptions\CustomerOptionInterface;
use Brille24\CustomerOptionsPlugin\Enumerations\CustomerOptionTypeEnum;
use Sylius\Behat\Page\Shop\Product\ShowPage as BaseShowPage;
use Symfony\Component\Routing\RouterInterface;

class ShowPage extends BaseShowPage
{
    /**
     * @param CustomerOptionInterface $customerOption
     * @return bool
     */
    public function hasCustomizationFor(CustomerOptionInterface $customerOption){

        $result = $this->getDocument()->hasField($customerOption->getName());

        if(!$result){
            $fields = $this->getDocument()->findAll('css', '.field');

            /** @var NodeElement $field */
            foreach ($fields as $field){
                $label = $field->find('css', 'label');

                if($label !== null && $label->getText() === $customerOption->getName()){
                    $result = $field->has('css', 'div[id^="sylius_add_to_cart_customer_options"]');
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param CustomerOptionInterface $customerOption
     * @param string $value
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function fillCustomerOption(CustomerOptionInterface $customerOption, string $value){
        if(CustomerOptionTypeEnum::isSelect($customerOption->getType()))
        {
            $this->getDocument()->selectFieldOption(
                $customerOption->getName(), $value,
                $customerOption->getType() === CustomerOptionTypeEnum::MULTI_SELECT
            );
        }elseif(CustomerOptionTypeEnum::isDate($customerOption->getType()))
        {
            /** @var NodeElement[] $labels */
            $labels = $this->getDocument()->findAll('css', 'div > label');

            $field = null;
            foreach ($labels as $label){
                if($label->getText() === $customerOption->getName()){
                    $field = $label->getParent();
                    break;
                }
            }

            $dateValue = new \DateTime($value);

            $dateFields = $customerOption->getType() === CustomerOptionTypeEnum::DATETIME ?
                $field->find('css', 'div[id$="date"]') :
                $field->find('css', 'div')
            ;

            $day = $dateFields->find('css', 'select[id$="day"]');
            $month = $dateFields->find('css', 'select[id$="month"]');
            $year = $dateFields->find('css', 'select[id$="year"]');

            $day->selectOption($dateValue->format('j'));
            $month->selectOption($dateValue->format('M'));
            $year->selectOption($dateValue->format('Y'));

            $timeFields = $field->find('css', 'div[id$="time"]');

            if($timeFields !== null){
                $hour = $timeFields->find('css', 'select[id$="hour"]');
                $minute = $timeFields->find('css', 'select[id$="minute"]');

                $hour->selectOption($dateValue->format('H'));
                $minute->selectOption($dateValue->format('i'));
            }
        }elseif($customerOption->getType() === CustomerOptionTypeEnum::BOOLEAN) {
            $this->checkField($customerOption->getName(), boolval($value));
        }else {
            $this->getDocument()->fillField($customerOption->getName(), $value);
        }
    }

    private function checkField($fieldName, $state){
        if($this->getDocument()->hasCheckedField($fieldName)){
            if($state === false){
                $this->getDocument()->uncheckField($fieldName);
            }
        }else{
            if($state === true){
                $this->getDocument()->checkField($fieldName);
            }
        }
    }

    /**
     * @return bool
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function hasRequiredCustomerOptionValidationMessage(){
        $message = "brille24.form.customer_options.required";

        return $this->hasValidationMessage($message);
    }

    /**
     * @return bool
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function hasInvalidCustomerOptionValidationMessage(){
        $message = "This value is not valid.";

        return $this->hasValidationMessage($message);
    }

    /**
     * @return bool
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function hasOptionOutOfBoundsValidationMessage(){
        $message = "This value should be";

        if(!$this->hasElement('validation_errors')){
            return false;
        }

        $errors = $this->getElement('validation_errors')->getText();

        return strpos($this->getElement('validation_errors')->getText(), $message) !== false;
    }

    /**
     * @param string $message
     * @return bool
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function hasValidationMessage(string $message){
        if(!$this->hasElement('validation_errors')){
            return false;
        }

        return $this->getElement('validation_errors')->getText() === $message;
    }
}