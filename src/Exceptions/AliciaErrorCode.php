<?php

namespace Hans\Alicia\Exceptions;

    class AliciaErrorCode
    {
        public const EXTERNAL_LINK_STORE_FAILED = 5001;
        public const UPLOAD_FAILED = 5002;
        public const LINK_IS_INVALID = 5003;
        public const NOT_ALLOWED_TO_DOWNLOAD = 5004;
        public const UNKNOWN_EXTENSION = 5005;
        public const UNKNOWN_FIELD_TYPE = 5006;
        public const UNKNOWN_FILE_TYPE = 5007;
        public const FAILED_TO_PROCESS_MODEL = 5008;
        public const FAILED_TO_DELETE_RESOURCE_MODEL = 5009;
        public const INVALID_MODEL_TO_EXPORT = 5010;
        public const MODEL_IS_EXTERNAL_ALREADY = 5011;
        public const FAILED_TO_MAKE_MODEL_EXTERNAL = 5012;
        public const FILE_DOEST_NOT_EXIST = 5013;
        public const FAILED_TO_MAKE_RESOURCE_FROM_FILE = 5014;
        public const FAILED_TO_TAKE_A_FRAME_FROM_VIDEO = 5015;
    }
