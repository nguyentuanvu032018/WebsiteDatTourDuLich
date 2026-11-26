<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

class TourDetail
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array<string, mixed> */
    public function findByTourId(int $tourId): array
    {
        $defaults = $this->defaults($tourId);
        if ($tourId <= 0) {
            return $defaults;
        }

        try {
            $stmt = $this->db->prepare('SELECT * FROM TourDetails WHERE tourId = :tourId LIMIT 1');
            $stmt->execute(['tourId' => $tourId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $detail = $row ? array_merge($defaults, normalize_db_row($row)) : $defaults;

            $itineraryStmt = $this->db->prepare(
                'SELECT itineraryId, tourId, dayNumber, title, description, meals, accommodation
                   FROM TourItinerary
                  WHERE tourId = :tourId
                  ORDER BY dayNumber ASC, itineraryId ASC'
            );
            $itineraryStmt->execute(['tourId' => $tourId]);
            $detail['itinerary'] = normalize_db_rows($itineraryStmt->fetchAll(PDO::FETCH_ASSOC));
            $detail['itineraryText'] = $this->toItineraryText($detail['itinerary']);

            return $detail;
        } catch (Throwable) {
            // The base tour detail page must still work before the migration is
            // executed. Advanced sections will simply show their fallback text.
            return $defaults;
        }
    }

    /** @return array<string, string> */
    public function validate(array $data): array
    {
        $errors = [];
        if (mb_strlen(trim((string)($data['departurePoint'] ?? ''))) > 150) {
            $errors['departurePoint'] = 'Điểm khởi hành tối đa 150 ký tự.';
        }
        if (mb_strlen(trim((string)($data['transport'] ?? ''))) > 200) {
            $errors['transport'] = 'Phương tiện tối đa 200 ký tự.';
        }
        if (mb_strlen(trim((string)($data['accommodation'] ?? ''))) > 200) {
            $errors['accommodation'] = 'Thông tin lưu trú tối đa 200 ký tự.';
        }
        if (mb_strlen(trim((string)($data['meals'] ?? ''))) > 200) {
            $errors['meals'] = 'Thông tin bữa ăn tối đa 200 ký tự.';
        }
        if (mb_strlen(trim((string)($data['suitableFor'] ?? ''))) > 255) {
            $errors['suitableFor'] = 'Đối tượng phù hợp tối đa 255 ký tự.';
        }
        if (mb_strlen(trim((string)($data['itineraryText'] ?? ''))) > 30000) {
            $errors['itineraryText'] = 'Lịch trình quá dài.';
        }

        return $errors;
    }

    public function saveForTour(int $tourId, array $data): bool
    {
        if ($tourId <= 0 || $this->validate($data)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO TourDetails (
                    tourId, departurePoint, transport, accommodation, meals, suitableFor,
                    highlights, includedServices, excludedServices, notes,
                    cancellationPolicy, updatedAt
                 ) VALUES (
                    :tourId, :departurePoint, :transport, :accommodation, :meals, :suitableFor,
                    :highlights, :includedServices, :excludedServices, :notes,
                    :cancellationPolicy, CURRENT_TIMESTAMP
                 )
                 ON CONFLICT (tourId) DO UPDATE SET
                    departurePoint = EXCLUDED.departurePoint,
                    transport = EXCLUDED.transport,
                    accommodation = EXCLUDED.accommodation,
                    meals = EXCLUDED.meals,
                    suitableFor = EXCLUDED.suitableFor,
                    highlights = EXCLUDED.highlights,
                    includedServices = EXCLUDED.includedServices,
                    excludedServices = EXCLUDED.excludedServices,
                    notes = EXCLUDED.notes,
                    cancellationPolicy = EXCLUDED.cancellationPolicy,
                    updatedAt = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                'tourId' => $tourId,
                'departurePoint' => trim((string)($data['departurePoint'] ?? '')),
                'transport' => trim((string)($data['transport'] ?? '')),
                'accommodation' => trim((string)($data['accommodation'] ?? '')),
                'meals' => trim((string)($data['meals'] ?? '')),
                'suitableFor' => trim((string)($data['suitableFor'] ?? '')),
                'highlights' => trim((string)($data['highlights'] ?? '')),
                'includedServices' => trim((string)($data['includedServices'] ?? '')),
                'excludedServices' => trim((string)($data['excludedServices'] ?? '')),
                'notes' => trim((string)($data['notes'] ?? '')),
                'cancellationPolicy' => trim((string)($data['cancellationPolicy'] ?? '')),
            ]);

            $delete = $this->db->prepare('DELETE FROM TourItinerary WHERE tourId = :tourId');
            $delete->execute(['tourId' => $tourId]);

            $insert = $this->db->prepare(
                'INSERT INTO TourItinerary (tourId, dayNumber, title, description, meals, accommodation)
                 VALUES (:tourId, :dayNumber, :title, :description, :meals, :accommodation)'
            );
            foreach ($this->parseItineraryText((string)($data['itineraryText'] ?? '')) as $day) {
                $insert->execute(['tourId' => $tourId] + $day);
            }

            $this->db->commit();
            return true;
        } catch (Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /** @return array<string, mixed> */
    private function defaults(int $tourId): array
    {
        return [
            'tourId' => $tourId,
            'departurePoint' => '',
            'transport' => '',
            'accommodation' => '',
            'meals' => '',
            'suitableFor' => '',
            'highlights' => '',
            'includedServices' => '',
            'excludedServices' => '',
            'notes' => '',
            'cancellationPolicy' => '',
            'itinerary' => [],
            'itineraryText' => '',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function parseItineraryText(string $text): array
    {
        $items = [];
        $usedDayNumbers = [];
        $fallbackDay = 1;

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 5));
            $dayNumber = isset($parts[0]) && preg_match('/\d+/', $parts[0], $match)
                ? max(1, (int)$match[0])
                : $fallbackDay;

            while (isset($usedDayNumbers[$dayNumber])) {
                $dayNumber++;
            }
            $usedDayNumbers[$dayNumber] = true;

            $items[] = [
                'dayNumber' => $dayNumber,
                'title' => mb_substr($parts[1] ?? ('Ngày ' . $dayNumber), 0, 200),
                'description' => $parts[2] ?? '',
                'meals' => mb_substr($parts[3] ?? '', 0, 100),
                'accommodation' => mb_substr($parts[4] ?? '', 0, 150),
            ];
            $fallbackDay = $dayNumber + 1;
        }

        return $items;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function toItineraryText(array $items): string
    {
        $lines = [];
        foreach ($items as $item) {
            $description = preg_replace('/\R/u', ' ', trim((string)($item['description'] ?? ''))) ?? '';
            $lines[] = implode('|', [
                'Ngày ' . (int)($item['dayNumber'] ?? 1),
                trim((string)($item['title'] ?? '')),
                $description,
                trim((string)($item['meals'] ?? '')),
                trim((string)($item['accommodation'] ?? '')),
            ]);
        }

        return implode(PHP_EOL, $lines);
    }
}
