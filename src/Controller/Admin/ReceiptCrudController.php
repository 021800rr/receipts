<?php

namespace App\Controller\Admin;

use App\Entity\Receipt;
use App\Form\ReceiptLineType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

final class ReceiptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Receipt::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('household', 'Gospodarstwo')->setRequired(true);
        yield AssociationField::new('store', 'Sklep')->setRequired(true);
        yield DateField::new('purchaseDate', 'Data zakupu')->setRequired(true);
        yield TextField::new('notes', 'Uwagi')->hideOnIndex();

        // Suma nagłówka (zł) – tylko do odczytu na liście; edycję trzymaj po Twojej stronie (liczona z pozycji)
        yield NumberField::new('totalAmount', 'Suma (zł)')
            ->onlyOnIndex()
            ->setNumDecimals(2);

        // >>> TU JEST “MIEJSCE NA POZYCJE” <<<
        yield CollectionField::new('lines', 'Pozycje')
            ->setEntryType(ReceiptLineType::class)
            ->allowAdd(true)
            ->allowDelete(true)
            ->renderExpanded(true)   // wygodny układ pionowy
            ->setFormTypeOptions([
                'by_reference' => false, // WYMAGANE, aby działały addLine/removeLine
                'entry_options' => [
                    'row_attr' => ['data-controller' => 'receipt-line']
                ],
                'attr' => ['data-controller' => 'receipt-line'],
            ])
            ->hideOnIndex()
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('logname')
            ->addAssetMapperEntry('line-total');
    }
}
