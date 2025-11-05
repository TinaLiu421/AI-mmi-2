<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use App\Support\DestinationsServing;

class Agents extends WebController {
    
    public function index() {
        // set meta
        $this->pageMeta(
        [
            'title'         =>  $this->_page_lang['list_agents'],
            'description'   =>  '',
            'image'         =>  ''
        ]);
        
        $incldued_country_id = [];
        if(!empty($this->_visa_countries)) {
            foreach($this->_visa_countries as $country) {
                $incldued_country_id[] = $country['id'];
            }
        }
        $list_agents = $this->_member_model->getAgentByCountryID($incldued_country_id);

        $visaCountriesMap = [];
        $generateKeys = function($text) {
            $keys = [];
            if (is_string($text)) {
                $base = strtolower(trim($text));
                if ($base !== '') {
                    $keys[] = $base;
                    if (strpos($base, '(') !== false) {
                        $beforeParenthesis = strtolower(trim(preg_replace('/\(.*/', '', $base)));
                        if ($beforeParenthesis !== '') {
                            $keys[] = $beforeParenthesis;
                        }
                    }
                    $alnum = preg_replace('/[^a-z0-9]/', '', $base);
                    if ($alnum !== '') {
                        $keys[] = $alnum;
                    }
                }
            }
            return array_values(array_unique(array_filter($keys)));
        };

        $visaCountriesTitleMap = [];
        if (!empty($this->_visa_countries)) {
            foreach ($this->_visa_countries as $vc) {
                $visaCountriesMap[(int) $vc['id']] = $vc;
                foreach ($generateKeys($vc['title']) as $key) {
                    if ($key !== '' && !isset($visaCountriesTitleMap[$key])) {
                        $visaCountriesTitleMap[$key] = $vc;
                    }
                }
            }
        }

        $allowedDestinationIds = [1, 2, 13, 14]; // Australia, Canada, UK, US
        $destinationsById = [];
        foreach (DestinationsServing::all() as $destination) {
            if (!in_array((int) $destination['id'], $allowedDestinationIds, true)) {
                continue;
            }

            $photoFlag = '';
            if (!empty($destination['flag_asset']) && file_exists(public_path($destination['flag_asset']))) {
                $photoFlag = $this->generateImage(['file_path' => $destination['flag_asset']], 300, 150, true);
            }
            $destinationUrl = '';

            $matchedVisaCountry = null;
            if (!empty($destination['visa_country_id']) && isset($visaCountriesMap[$destination['visa_country_id']])) {
                $matchedVisaCountry = $visaCountriesMap[$destination['visa_country_id']];
            } else {
                $aliasKeys = [];
                if (!empty($destination['aliases'])) {
                    foreach ((array)$destination['aliases'] as $alias) {
                        $aliasKeys = array_merge($aliasKeys, $generateKeys($alias));
                    }
                }
                $candidateKeys = array_merge(
                    $generateKeys($destination['visa_country_label'] ?? ''),
                    $generateKeys($destination['label']),
                    $aliasKeys
                );
                foreach ($candidateKeys as $candidateKey) {
                    if (isset($visaCountriesTitleMap[$candidateKey])) {
                        $matchedVisaCountry = $visaCountriesTitleMap[$candidateKey];
                        break;
                    }
                }
            }

            if ($matchedVisaCountry) {
                if (empty($photoFlag)) {
                    $photoFlag = $matchedVisaCountry['photo_flag'] ?? '';
                }
                $destinationUrl = $matchedVisaCountry['url'] ?? '';
                $destination['visa_country_id'] = $matchedVisaCountry['id'] ?? $destination['visa_country_id'];
            }
            if (empty($photoFlag)) {
                $photoFlag = $this->generateImage(null, 300, 150, true);
            }

            $destination['photo_flag'] = $photoFlag;
            $destination['url'] = $destinationUrl;
            $destination['agents'] = [];
            $destinationsById[(int) $destination['id']] = $destination;
        }

        if (!empty($list_agents)) {
            foreach ($list_agents as &$agent) {
                $agent['countries_serving'] = DestinationsServing::fromStorage($agent['countries_serving'] ?? []);
                $assigned = false;

                if (!empty($agent['countries_serving'])) {
                    foreach ($agent['countries_serving'] as $destId) {
                        $destId = (int) $destId;
                        if (isset($destinationsById[$destId])) {
                            $destinationsById[$destId]['agents'][] = $agent;
                            $assigned = true;
                        }
                    }
                }

                // Agents serving destinations outside allowed list are intentionally hidden for now
            }
            unset($agent);
        }

        return $this->pageData(
        [
            'list'         =>  $list_agents,
            'destinations' =>  array_values($destinationsById),
        ])->pageView();
    }
}
