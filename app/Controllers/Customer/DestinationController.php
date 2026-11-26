<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\Location;
use App\Models\Tour;

class DestinationController extends Controller
{
    public function show(int $locationId): void
    {
        $pdo = PDO();
        $location = (new Location($pdo))->findLocationById($locationId);
        if (!$location) {
            $this->sendNotFound();
        }

        $this->sendPage('frontend/destinations/show', [
            'location' => $location,
            'guide' => $this->guideFor($location->locationName, $location->city),
            'tours' => (new Tour($pdo))->search(-1, $locationId, '', 4, 0),
        ]);
    }

    /** @return array<string, mixed> */
    private function guideFor(string $name, string $city): array
    {
        $key = mb_strtolower($name);
        $catalog = [
            'đà nẵng' => [
                'tagline' => 'Thành phố biển năng động',
                'summary' => 'Nơi biển xanh gặp những công trình biểu tượng và nhịp sống miền Trung đầy sức sống.',
                'temperature' => '28–33°C',
                'season' => 'Tháng 2 – Tháng 8',
                'weather' => 'Biển đẹp, ít mưa',
                'experiences' => ['Bà Nà Hills', 'Cầu Rồng', 'Biển Mỹ Khê', 'Phố cổ Hội An'],
            ],
            'phú quốc' => [
                'tagline' => 'Thiên đường biển đảo',
                'summary' => 'Đảo ngọc với bãi biển trong xanh, hoàng hôn rực rỡ và những trải nghiệm nghỉ dưỡng đáng nhớ.',
                'temperature' => '25–32°C',
                'season' => 'Tháng 11 – Tháng 4',
                'weather' => 'Nắng đẹp, biển êm',
                'experiences' => ['Bãi Sao', 'Hòn Thơm', 'Làng chài Hàm Ninh', 'Chợ đêm Phú Quốc'],
            ],
            'đà lạt' => [
                'tagline' => 'Thành phố ngàn hoa',
                'summary' => 'Không khí se lạnh, đồi thông xanh và những khu vườn đầy sắc màu tạo nên một Đà Lạt rất riêng.',
                'temperature' => '15–24°C',
                'season' => 'Quanh năm',
                'weather' => 'Mát mẻ, trong lành',
                'experiences' => ['Hồ Xuân Hương', 'Đồi chè Cầu Đất', 'Thung lũng Tình Yêu', 'Chợ đêm Đà Lạt'],
            ],
            'nha trang' => [
                'tagline' => 'Vịnh biển xanh trong',
                'summary' => 'Thành phố biển chan hòa nắng với những hòn đảo đẹp, hoạt động thể thao nước và ẩm thực phong phú.',
                'temperature' => '26–32°C',
                'season' => 'Tháng 1 – Tháng 8',
                'weather' => 'Nhiều nắng, ít mưa',
                'experiences' => ['Vịnh Nha Trang', 'Hòn Mun', 'Tháp Bà Ponagar', 'Bãi Dài'],
            ],
            'hạ long' => [
                'tagline' => 'Kỳ quan thiên nhiên',
                'summary' => 'Hàng nghìn đảo đá vôi giữa làn nước xanh tạo nên một trong những cảnh quan ngoạn mục nhất Việt Nam.',
                'temperature' => '20–30°C',
                'season' => 'Tháng 3 – Tháng 10',
                'weather' => 'Dịu mát, cảnh đẹp',
                'experiences' => ['Du thuyền trên vịnh', 'Hang Sửng Sốt', 'Đảo Ti Tốp', 'Làng chài Cửa Vạn'],
            ],
            'sapa' => [
                'tagline' => 'Nét đẹp vùng cao',
                'summary' => 'Ruộng bậc thang, biển mây và bản làng dân tộc tạo nên hành trình khám phá đậm bản sắc Tây Bắc.',
                'temperature' => '10–23°C',
                'season' => 'Tháng 3 – Tháng 11',
                'weather' => 'Mát lạnh, nhiều mây',
                'experiences' => ['Đỉnh Fansipan', 'Bản Cát Cát', 'Đèo Ô Quy Hồ', 'Thung lũng Mường Hoa'],
            ],
        ];

        foreach ($catalog as $needle => $guide) {
            if (str_contains($key, $needle)) {
                return $this->withExperienceDescriptions($guide, $name);
            }
        }

        return $this->withExperienceDescriptions([
            'tagline' => 'Điểm đến giàu trải nghiệm',
            'summary' => $name . ' mang đến cảnh quan đặc sắc, văn hóa bản địa và nhiều hành trình phù hợp cho mọi du khách.',
            'temperature' => '18–32°C',
            'season' => 'Quanh năm',
            'weather' => 'Thời tiết đa dạng',
            'experiences' => [
                'Khám phá ' . $name,
                'Văn hóa bản địa',
                'Cảnh quan thiên nhiên',
                'Ẩm thực ' . ($city !== '' ? $city : $name),
            ],
        ], $name);
    }

    /** @param array<string, mixed> $guide
     *  @return array<string, mixed>
     */
    private function withExperienceDescriptions(array $guide, string $destination): array
    {
        $experiences = array_values($guide['experiences'] ?? []);
        $templates = [
            'Khám phá %s và cảm nhận những nét đặc trưng tạo nên sức hút riêng của %s.',
            'Tìm hiểu %s để đến gần hơn với đời sống, văn hóa và con người tại %s.',
            'Chiêm ngưỡng %s, lưu lại những khoảnh khắc đẹp giữa thiên nhiên của %s.',
            'Dành thời gian trải nghiệm %s và hoàn thiện hành trình đáng nhớ tại %s.',
        ];

        $guide['experienceDescriptions'] = [];
        foreach ($experiences as $index => $experience) {
            $template = $templates[$index] ?? $templates[array_key_last($templates)];
            $guide['experienceDescriptions'][] = sprintf($template, $experience, $destination);
        }

        return $guide;
    }
}
