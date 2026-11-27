<?php

namespace App\Entity;

/**
 * Une prise alimentaire datée : un nom libre ("Petit-déjeuner", "Dîner"...),
 * une date, et la liste de ses produits.
 */
class Meal
{
    private ?int $id;
    private string $date;
    private string $name;
    /** @var MealItem[] */
    private array $items;

    /**
     * @param MealItem[] $items
     */
    public function __construct(?int $id, string $date, string $name, array $items = [])
    {
        $this->id = $id;
        $this->date = $date;
        $this->name = $name;
        $this->items = $items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return MealItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(MealItem $item): void
    {
        $this->items[] = $item;
    }

    public function getTotalKcal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->getKcal();
        }

        return round($total, 2);
    }
}
