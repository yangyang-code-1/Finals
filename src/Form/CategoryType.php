<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'e.g., Character Design']
            ])
            ->add('slug', TextType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'character-design']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Describe what this category includes...']
            ])
            ->add('icon', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'bi-palette-fill']
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Active Category',
                'data' => true  // Default to active
            ])
            ->add('position', NumberType::class, [
                'required' => false,
                'attr' => ['min' => 1, 'placeholder' => '1']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
