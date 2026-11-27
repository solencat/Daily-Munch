<?php

namespace App\Entity;

/**
 * Un produit d'un repas enregistré.
 *
 * Les valeurs nutritionnelles sont celles du catalogue au moment de l'ajout
 * (kcal_100g est recopié en base) : un repas passé ne change pas quand Open
 * Food Facts met à jour la fiche du produit.
 */
class MealItem
{
    private ?string $productCode;
    private string $productName;
    private float $quantityG;
    private float $kcal100g;
    private float $kcal;

    public function __construct(
        ?string $productCode,
        string $productName,
        float $quantityG,
        float $kcal100g,
        ?float $kcal = null,
    ) {
        $this->productCode = $productCode;
        $this->productName = $productName;
        $this->quantityG = $quantityG;
        $this->kcal100g = $kcal100g;
        $this->kcal = $kcal ?? round($quantityG * $kcal100g / 100, 2);
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantityG(): float
    {
        return $this->quantityG;
    }

    public function getKcal100g(): float
    {
        return $this->kcal100g;
    }

    public function getKcal(): float
    {
        return $this->kcal;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function hydrate(array $data): MealItem
    {
        return new MealItem(
            isset($data["product_code"]) && $data["product_code"] !== null
                ? (string) $data["product_code"]
                : null,
            (string) $data["product_name"],
            (float) $data["quantity_g"],
            (float) $data["kcal_100g"],
            isset($data["kcal"]) ? (float) $data["kcal"] : null,
        );
    }
}
