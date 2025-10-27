<?php

namespace App\Controller\Admin;

use App\Entity\Receipt;
use App\Form\ReceiptLineType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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

        // Suma nagłówka (gr) – tylko do odczytu na liście; edycję trzymaj po Twojej stronie (liczona z pozycji)
        yield IntegerField::new('totalAmountGrosze', 'Suma (gr)')
            ->onlyOnIndex();

        // >>> TU JEST “MIEJSCE NA POZYCJE” <<<
        yield CollectionField::new('lines', 'Pozycje')
            ->setEntryType(ReceiptLineType::class)
            ->allowAdd(true)
            ->allowDelete(true)
            ->renderExpanded(true)   // wygodny układ pionowy
            ->setFormTypeOptions([
                'by_reference' => false, // WYMAGANE, aby działały addLine/removeLine
            ])
            ->hideOnIndex()
        ;
    }
}
