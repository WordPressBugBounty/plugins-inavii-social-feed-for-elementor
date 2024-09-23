<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class AccountDelete
{
    private $api;
    private $feed;
    private $account;

    public function __construct()
    {
        $this->api = new ApiResponse();
        $this->feed = new FeedPostType();
        $this->account = new AccountPostType();
    }

    public function delete(WP_REST_Request $request): WP_REST_Response
    {
        $accountID = $request->get_param('id');

        $this->feed->deleteFeedsRelatedToAccount($accountID);

        $this->deleteSupportOlderVersions($accountID);

        return $this->api->response([]);
    }

    /**
     * @deprecated 2.4.3.
     */
    private function deleteSupportOlderVersions(int $accountID)
    {
        $feedsIds = $this->feed->getRelatedFeedsIds($accountID);

        if ($feedsIds) {
            foreach ($feedsIds as $id) {
                $this->feed->delete($id);
            }
        }

        $this->account->deleteMedia($accountID);
        $this->account->delete($accountID);
    }
}
