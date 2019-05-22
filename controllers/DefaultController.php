<?php

class DefaultController
{
    const SOURCES = [
        1 => 'https://www.landwirtschaftskammer.de/bildung/pferdewirt/betriebe/',
        2 => 'https://www.talente-gesucht.de/index.cfm/action/jobs.html?job=Pferdewirt%2Fin',
    ];
    public static function error404()
    {
        $content = <<< EOT
404
EOT;

        return $content;
    }

    public static function index()
    {
        $content = <<< EOT
<form action="/scrape" method="GET">
    <select name="source">
        <option value="1">https://www.landwirtschaftskammer.de/bildung/pferdewirt/betriebe/</option>
        <option value="2">https://www.talente-gesucht.de/index.cfm/action/jobs.html?job=Pferdewirt%2Fin</option>
    </select>
    <input type="submit" value="scrape" />
</form>
EOT;

        return $content;
    }

    public static function scrape()
    {
        $source = self::SOURCES[$_GET['source']] ?? null;
        if(!$source) {
            throw new \Exception('No such option.');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $source);
        $content = curl_exec($curl);
        curl_close($curl);

        preg_match_all('#<area[^>]+?href="([^"]+?)"[^>]+?>#s', $content, $links, PREG_SET_ORDER);
        $contentRegions = [];
        $filename = date('Y-m-d_H-i-s') . '.csv';
        $csv = fopen(ROOT_DIR . '/storage/csv/' . $filename, 'w');
        $rows = [];
        $order = 1;
        foreach($links as $link) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $source . $link[1]);
            $contentRegions[$link[1]] = curl_exec($curl);
            curl_close($curl);

            preg_match_all('#<div\s+class="ym-grid\s+linearize-level-2"><div\s+class="ym-g50\s+ym-gl"><div\s+class="ym-gbox"><p>(.*?)<br>(.*?)<br>(.*?)<br>(?:Telefon:\s+(.*?)<br>)?(?:Telefax:\s+(.*?)<br>)?(?:E-Mail:\s+<a\s+href="mailto:([^"]*?)".*?<br>)?.*?(?:<a\s+href="([^"]*?)".*?)?</p>.*?<div\s+class="ym-g50\s+ym-gr"><div\s+class="ym-gbox"><p>(.*?)</p>.*?<hr\s+class="trenner">#s', $contentRegions[$link[1]], $businesses, PREG_SET_ORDER);

            foreach($businesses as $business) {
                $fields = [];
                $fields['order'] = $order;
                $fields['name'] = $business[1];
                $fields['street'] = $business[2];
                $postCodeCity = explode('&nbsp;', $business[3]);
                $fields['postCode'] = $postCodeCity[0];
                $fields['city'] = $postCodeCity[1];
                $fields['phone'] = $business[4];
                $fields['fax'] = $business[5];
                $fields['email'] = $business[6];
                $fields['website'] = $business[7];
                $fields['specialization'] = implode('|', preg_split('#\s*<br>\s*#', $business[8]));
                fputcsv($csv, $fields);
                $rows[] = $fields;
                $order++;
            }
        }
        fclose($csv);

        $headings = '<th>' . implode('</th><th>', ['#', 'Name', 'Street', 'Post Code', 'City', 'Phone', 'Fax', 'Email', 'Website', 'Specialization']) . '</th>';
        $tableRows = '<tr>' . implode('</tr><tr>', array_map(function($row) {return '<td>' . implode('</td><td>', $row) . '</td>';}, $rows)) . '</tr>';
        $content = <<< EOT
<a href="/storage/csv/$filename">Download CSV</a>
<table border="1">
<thead>
$headings
</thead>
<tbody>
$tableRows
</tbody>
</table>
EOT;

        return $content;
    }
}