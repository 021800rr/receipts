<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ReceiptLine;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReceiptLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // produkt (słownik)
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'placeholder' => '',
                'label' => 'Produkt',
                'query_builder' => function (EntityRepository $productRepository) {
                    return $productRepository->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'attr' => [
                    'class' => 'js-product-select',
                    'data-search-url' => '/admin/api/products',
                    'data-create-url' => '/admin/api/products',
                ],
            ])
            // ilość (DECIMAL)
            ->add('quantity', NumberType::class, [
                'label' => 'Ilość',
                'scale' => 3,
                'html5' => false,
                'data' => 1,
                'attr' => [
                    'class' => 'rl-quantity',
                    'data-receipt-line-target' => 'quantity',
                    'inputmode' => 'decimal',
                ],
            ])
            // jednostka (opcjonalnie)
            ->add('unit', ChoiceType::class, [
                'label' => 'Jednostka',
                'required' => false,
                'choices' => [
                    'szt' => 'szt',
                    'kg'  => 'kg',
                ],
                // 'placeholder' => '',
                'placeholder' => false,
                'attr' => [
                    'class' => 'rl-unit-select',
                ],
            ])
            // pola w złotych (mapowane do helperów encji)
            ->add('unitPrice', NumberType::class, [
                'label' => 'Cena jedn. (zł)',
                'scale' => 2,
                'required' => true,
                'mapped' => true,
                'property_path' => 'unitPrice',
                'html5' => false,
                'attr' => [
                    'class' => 'rl-unit-price',
                    'data-receipt-line-target' => 'unitPrice',
                    'inputmode' => 'decimal',
                    'placeholder' => 'np. 5,99 lub 5.99',
                ],
            ])
            ->add('lineTotal', NumberType::class, [
                'label' => 'Wartość pozycji (zł)',
                'scale' => 2,
                'required' => true,
                'mapped' => true,
                'property_path' => 'lineTotal',
                'html5' => false,
                'attr' => [
                    'class' => 'rl-line-total',
                    'data-receipt-line-target' => 'lineTotal',
                    'readonly' => 'readonly',
                    'inputmode' => 'decimal',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReceiptLine::class,
        ]);
    }
}
