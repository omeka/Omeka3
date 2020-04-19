<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class SiteForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {
        $this->setAttribute('id', 'site-form');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'title',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => 'URL slug', // @translate
            ],
            'attributes' => [
                'id' => 'slug',
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'o:summary',
            'type' => 'Textarea',
            'options' => [
                'label' => 'Summary', // @translate
            ],
            'attributes' => [
                'id' => 'summary',
                'required' => false,
            ],
        ]);
        if ('add' === $this->getOption('action')) {
            $this->add([
                'name' => 'o:assign_new_items',
                'type' => 'checkbox',
                'options' => [
                    'label' => 'Assign new items', // @translate
                ],
                'attributes' => [
                    'id' => 'assign_new_items',
                    'value' => true,
                ],
            ]);
        }

        $event = new Event('form.add_elements', $this);
        $triggerResult = $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();

        // Separate events because calling $form->getInputFilters()
        // resets everythhing
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }
}
