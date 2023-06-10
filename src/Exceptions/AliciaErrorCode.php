<?php

	namespace Hans\Alicia\Exceptions;

	class AliciaErrorCode {
		public const KEY_IS_NULL = 5001;
		public const EXTERNAL_LINK_STORE_FAILED = 5002;
		public const UPLOAD_FAILED = 5003;
		public const LINK_IS_INVALID = 5004;
		public const NOT_ALLOWED_TO_DOWNLOAD = 5005;
		public const UNKNOWN_EXTENSION = 5007;
		public const UNKNOWN_FIELD_TYPE = 5008;
		public const UNKNOWN_FILE_TYPE = 5009;
		public const MODEL_STORE_FAILED = 5010;
		public const EXPORT_CONFIG_NOT_SET = 5011;
		public const FAILED_TO_EXPORT = 5012;
		public const FAILED_TO_ACCESS_MODEL = 5013;
		public const URL_IS_INVALID = 5014;
		public const FAILED_TO_PROCESS_MODEL = 5015;
		public const FAILED_TO_DELETE_RESOURCE_MODEL = 5016;
		public const INVALID_MODEL_TO_EXPORT = 5017;
		public const MODEL_IS_EXTERNAL_ALREADY = 5018;
		public const FAILED_TO_MAKE_MODEL_EXTERNAL = 5019;
		public const FILE_DOEST_NOT_EXIST = 5020;
		public const FAILED_TO_MAKE_RESOURCE_FROM_FILE = 5021;
		public const FAILED_TO_TAKE_A_FRAME_FROM_VIDEO = 5022;
	}
