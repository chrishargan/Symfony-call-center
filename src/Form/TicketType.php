<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-group w-100']
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['class' => 'form-group w-100']
            ])
//            ->add('status')
//            ->add('createdDate')
//            ->add('updatedDate')
//            ->add('isEscalated')
//            ->add('closeReason')
//            ->add('priority')
//            ->add('customer')
//            ->add('agent')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
