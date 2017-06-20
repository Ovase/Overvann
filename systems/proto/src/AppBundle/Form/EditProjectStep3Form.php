<?php
namespace AppBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EditProjectStep3Form extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('captcha', CaptchaType::class, array('attr' => array('placeholder' => 'Skriv tegnene','help' => 'test catpcha'),
                'label' => 'Bevis at du ikke er en robot',
                'width' => 200,
                'height' => 50,
                'length' => 5,
                'quality' =>200,
                'keep_value' => true,
                'distortion' => false,
                'background_color' => [255, 255, 255]))
            ->add('save', SubmitType::class, array ('label' => 'Opprett prosjekt','attr'=>array('class'=>'btn btn-default')));
    }

    public function buildView(FormView $view, FormInterface $form, array $options) {
        parent::buildView($view, $form, $options);
        $view->vars['endMessage'] = 'Tekst her.';
    }

    public function getBlockPrefix() {
        return 'editProjectStep3';
    }

}
