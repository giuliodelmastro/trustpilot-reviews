<?php

/**
 * Trustpilot_Reviews
 * 
 * @author Giulio Delmastro <https://comshake.com>
 * 
 */


class Trustpilot_Reviews
{

    private $id;

    private $count;

    private $orderby;

    private $order;

    /**
     * Constructor.
     * @param string $id ID of Trustpilot account.
     * @param int $count defines the number of reviews to return. '-1' returns all reviews. Default: '-1'
     * @param string $order_by defines by which parameter to sort reviews. Default 'time' Accepts: 'time' or 'rating'
     * @param string $order Designates ascending or descending order of reviews. Default 'desc'. Accepts 'asc', 'desc'.
     */
    function __construct($id, $count = '-1', $orderby = 'time', $order = 'desc')
    {
        $this->id = $id;
        $this->count = $count;
        $this->orderby = $orderby;
        $this->order = $order;
    }

    /**
     * Retrieve the html content of the page.
     *
     * @param int    $page   Page number
     * @param string $permalink Post permalink.
     */
    public function get_data($page = 1)
    {

        $options = array(
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_POST           => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
        );

        $curl = curl_init("https://trustpilot.com/review/{$this->id}?languages=all" . (1 != $page ? "&page={$page}" : "") . "&sort=recency");
        curl_setopt_array($curl, $options);
        $data = curl_exec($curl);

        return $data;
    }

    /**
     * Check if reviews are paginated in multiple pages.
     *
     * @param DOMDocument $dom
     * @param string 
     */
    private function parse_pagination($dom)
    {
        $xpath = new DOMXpath($dom);

        $pagination = $xpath->query(".//*[contains(@name, 'pagination-button-')][not(contains(@name, 'pagination-button-next'))]");

        return $pagination->length ? $pagination->item($pagination->length - 1)->nodeValue : 1;
    }

    /**
     * Find and return the author of the review.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_consumer($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        return $xpath->query(".//*[@data-consumer-name-typography]", $context)->item(0)->nodeValue ?: '';
    }

    /**
     * Find and return the title of the review.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_title($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        return $xpath->query(".//*[@data-review-title-typography]", $context)->item(0)->nodeValue ?: '';
    }


    /**
     * Find and return review url.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_url($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        return $xpath->query(".//*[@data-review-title-typography]", $context)->item(0)->getAttribute('href') ?: '';
    }

    /**
     * Find and return review content.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_content($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        $content = $xpath->query(".//*[@data-service-review-text-typography]", $context);

        return $content->length ? $content->item(0)->nodeValue : '';
    }

    /**
     * Find and return the review rating.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_rating($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        return $xpath->query(".//div[@data-service-review-rating]", $context)->item(0)->getAttribute('data-service-review-rating') ?: '';
    }

    /**
     * Find and return review date time.
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_time($dom, $context)
    {
        $xpath = new DOMXpath($dom);

        return $xpath->query(".//*[@data-service-review-date-time-ago]", $context)->item(0)->getAttribute('datetime') ?: '';
    }

    /**
     * Find the data of the review
     *
     * @param DOMDocument $dom
     * @param DOMNode $context
     * @param string 
     */
    private function parse_data($dom, $context)
    {

        $parsed = array(
            'consumer' => $this->parse_consumer($dom, $context),
            'title' => $this->parse_title($dom, $context),
            'url' => $this->parse_url($dom, $context),
            'content' => $this->parse_content($dom, $context),
            'rating' => $this->parse_rating($dom, $context),
            'time' => $this->parse_time($dom, $context),
        );

        return $parsed;
    }

    /**
     * Sort reviews by set parameter
     *
     * @param array $data Array of reviews
     */
    private function sort(&$data)
    {

        if ('time' === $this->orderby) {

            if ('desc' === $this->order) {
                return $data;
            };
            $sorted = usort($data, function ($a, $b) {
                return strtotime($a['time']) <=> strtotime($b['time']);
            });
        } else if ('rating' === $this->orderby) {
            $sorted = usort($data, function ($a, $b) {
                return 'asc' === $this->order ? ($a['rating'] <=> $b['rating']) : ($b['rating'] <=> $a['rating']);
            });
        };

        return $sorted;
    }

    /**
     * Generate reviews
     */
    private function generate()
    {
        $data = $this->get_data();

        $dom = new DOMDocument('1.0');

        $dom->loadHTML($data, LIBXML_NOERROR);

        $parsed = [];

        $items = $dom->getElementsByTagName('article');

        $pagination = $this->parse_pagination($dom);

        foreach ($items as $item) {

            if (count($parsed) >= $this->count && $this->count != -1) :
                break;
            endif;

            $parsed[] = $this->parse_data($dom, $item);
        }

        if ($pagination > 1) {

            for ($page = 2; $page <= $pagination; $page++) {

                $data = $this->get_data($page);

                $dom->loadHTML($data, LIBXML_NOERROR);

                foreach ($items as $item) {

                    if (count($parsed) >= $this->count && $this->count != -1) :
                        break;
                    endif;

                    $parsed[] = $this->parse_data($dom, $item);
                }
            }
        }

        $this->sort($parsed);

        return $parsed;
    }


    /**
     * Generate a csv file with reviews
     *
     * @param array $path The location where to save the exported file
     * @param string $separator The delimiter of the csv fields
     */
    public function generate_csv($path, $separator = ',')
    {

        $data = $this->generate();

        $output = fopen(DEV_DIR_PATH . 'review.csv', 'a');

        fputcsv($output, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($output, $row, $separator);
        }

        fclose($output);
    }

    /**
     * Generate a xml file with reviews
     *
     * @param array $path The location where to save the exported file
     */
    public function generate_xml($path)
    {
        $data = $this->generate();

        $xml = new SimpleXMLElement('<reviews></reviews>');

        foreach ($data as $item) {

            $review = $xml->addChild('review');

            foreach ($item as $key => $value) {
                $review->addChild($key, $value);
            }
        }

        $xml->asXML($path);
    }

    /**
     * Return reviews
     */
    public function get_reviews()
    {
        return $this->generate();
    }
}
