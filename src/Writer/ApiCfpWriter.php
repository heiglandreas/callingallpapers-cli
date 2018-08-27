<?php
/**
 * Copyright (c) 2015-2016 Andreas Heigl<andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright 2015-2016 Andreas Heigl/callingallpapers.com
 * @license   http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version   0.0
 * @since     01.12.2015
 * @link      http://github.com/heiglandreas/callingallpapers-cli
 */
namespace Callingallpapers\Writer;

use Callingallpapers\CfpFilter\CfpFilterInterface;
use Callingallpapers\CfpFilter\FilterList;
use Callingallpapers\Entity\Cfp;
use Callingallpapers\ResultKeeper\Created;
use Callingallpapers\ResultKeeper\Error;
use Callingallpapers\ResultKeeper\Failure;
use Callingallpapers\ResultKeeper\ResultKeeper;
use Callingallpapers\ResultKeeper\Success;
use Callingallpapers\ResultKeeper\Updated;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\Console\Output\OutputInterface;

class ApiCfpWriter implements WriterInterface
{
    protected $baseUri;

    protected $bearerToken;

    protected $client;

    protected $output;

    private $filter = null;

    private $keeper;

    public function __construct($baseUri, $bearerToken, $client = null)
    {
        $this->baseUri     = $baseUri;
        $this->bearerToken = $bearerToken;
        if (null === $client) {
            $client = new Client([
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
        }
        $this->client = $client;
        $this->output = new NullOutput();
        $this->filter = new FilterList();
        $this->keeper = new ResultKeeper();
    }

    public function setFilter(CfpFilterInterface $filter)
    {
        $this->filter = $filter;
    }

    public function write(Cfp $cfp, $source)
    {
        $cfp = $this->filter->filter($cfp);

        $body = [
            'name'           => $cfp->conferenceName,
            'dateCfpEnd'     => $cfp->dateEnd->format('c'),
            'dateEventStart' => $cfp->eventStartDate->format('c'),
            'dateEventEnd'   => $cfp->eventEndDate->format('c'),
            'timezone'       => $cfp->timezone,
            'uri'            => $cfp->uri,
            'eventUri'       => $cfp->conferenceUri,
            'iconUri'        => $cfp->iconUri,
            'description'    => $cfp->description,
            'location'       => $cfp->location,
            'latitude'       => $cfp->latitude,
            'longitude'      => $cfp->longitude,
            'tags'           => $cfp->tags,
            'source'         => $source,
        ];

        if ($cfp->dateStart instanceof \DateTimeInterface) {
            $body['dateCfpStart'] = $cfp->dateStart->format('c');
        }

        try {
            $this->client->request('GET', sprintf(
                $this->baseUri . '/%1$s',
                sha1($cfp->conferenceUri)
            ), []);
            $exists = true;
        } catch (BadResponseException $e) {
            $exists = false;
        }

        try {
            if ($exists === false) {
                // Doesn't exist, so create it
                $response = $this->client->request('POST', sprintf(
                    $this->baseUri
                ), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->bearerToken,
                    ],
                    'form_params' => $body
                ]);
                $result = new Created($cfp->conferenceName);
            } else {
                // Exists, so update it
                $response = $this->client->request('PUT', sprintf(
                    $this->baseUri . '/%1$s',
                    sha1($cfp->conferenceUri)
                ), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->bearerToken,
                    ],
                    'form_params' => $body
                ]);
                $result = new Updated($cfp->conferenceName);
            }
        } catch (BadResponseException $e) {
            $this->keeper->addFailure(new Failure($cfp->conferenceName, $e));
            return $e->getMessage();
        } catch (\Exception $e) {
            $this->keeper->addError(new Error($cfp->conferenceName, $e));
            return $e->getMessage();
        }

        if ($response && ($response->getStatusCode() === 204 || $response->getStatusCode() === 200 || $response->getStatusCode() === 201)) {
            $this->keeper->add($result);
        }

        return (isset($response) && ($response->getStatusCode() === 204 || $response->getStatusCode() === 200 || $response->getStatusCode() === 201))?'Success':'Failure';
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setResultKeeper(ResultKeeper $resultKeeper)
    {
        $this->keeper = $resultKeeper;
    }
}
