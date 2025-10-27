<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ReceiptLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'placeholder' => '— wybierz produkt —',
                'label' => 'Produkt',
            ])
            // ilość (DECIMAL)
            ->add('quantity', NumberType::class, [
                'label' => 'Ilość',
                'scale' => 3,
            ])
            // jednostka (opcjonalnie)
            ->add('unit', TextType::class, [
                'label' => 'Jednostka',
                'required' => false,
            ])
            // cena jednostkowa w groszach (int/bigint) – wpisujemy jako liczba całkowita
            ->add('unitPriceGrosze', IntegerType::class, [
                'label' => 'Cena jedn. (gr)',
            ])
            // wartość pozycji w groszach (liczone u Ciebie w encji/serwisie; tu pozwalamy nadpisać lub policz automatem)
            ->add('lineTotalGrosze', IntegerType::class, [
                'label' => 'Wartość pozycji (gr)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReceiptLine::class,
        ]);
    }
}
