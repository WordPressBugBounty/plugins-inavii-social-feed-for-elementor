<?php
declare( strict_types=1 );

namespace Inavii\Instagram\Account\Dto;

final class AccountCredentials {
	public int $id;
	public string $igAccountId;
	public string $accessToken;
	public string $accountType;
	public string $connectType;

	public function __construct(
		int $id,
		string $igAccountId,
		string $accessToken,
		string $accountType,
		string $connectType
	) {
		$this->id          = $id;
		$this->igAccountId = $igAccountId;
		$this->accessToken = $accessToken;
		$this->accountType = $accountType;
		$this->connectType = $connectType;
	}
}
