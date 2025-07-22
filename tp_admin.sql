/*
 Navicat Premium Dump SQL

 Source Server         : 本地
 Source Server Type    : MySQL
 Source Server Version : 80012 (8.0.12)
 Source Host           : 127.0.0.1:3306
 Source Schema         : tp_admin

 Target Server Type    : MySQL
 Target Server Version : 80012 (8.0.12)
 File Encoding         : 65001

 Date: 22/07/2025 11:06:39
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin`  (
  `admin_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '管理员名称',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '加密密码',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `real_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '真实姓名',
  `nick_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '昵称',
  `status` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '用户状态',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '' COMMENT '头像',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`admin_id`) USING BTREE,
  UNIQUE INDEX `idx_name`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin
-- ----------------------------
INSERT INTO `admin` VALUES (1, 'admin', '$2y$10$RMfYvLtuFMURQ.5VesDdSOpUc/Lxs4KjZWBDrRSP/iYwrS4E5eeUW', '2025-06-20 15:52:31', '2025-07-22 11:05:20', '李四', '张三', 1, 'http://localhost:8000/storage/uploads/20250721/5ff0656cfff7bb416c9d44dd34a5fce7.png', NULL);
INSERT INTO `admin` VALUES (2, 'manager', '$2y$10$foYvXsO.6vsNEQ9P0ltS4ur7sd0aTu2U.O5b0voPwNGAt4YZV8OIe', '2025-06-20 15:53:52', '2025-07-21 18:21:18', '张三', '李四', 1, 'http://localhost:8000/storage/uploads/20250721\\2051a22f230ea58e96163bc29926ecd0.png', NULL);

-- ----------------------------
-- Table structure for admin_role
-- ----------------------------
DROP TABLE IF EXISTS `admin_role`;
CREATE TABLE `admin_role`  (
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT '管理员ID',
  `role_id` int(10) UNSIGNED NOT NULL COMMENT '角色ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `role_id`) USING BTREE,
  INDEX `fk_role`(`role_id` ASC) USING BTREE,
  CONSTRAINT `fk_admin_role_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_admin_role_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员-角色关联' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_role
-- ----------------------------

-- ----------------------------
-- Table structure for admin_token
-- ----------------------------
DROP TABLE IF EXISTS `admin_token`;
CREATE TABLE `admin_token`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) UNSIGNED NOT NULL COMMENT '管理员ID',
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '认证令牌',
  `client_type` enum('admin','weapp') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'admin' COMMENT '客户端类型',
  `platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '登录平台',
  `expire_time` int(10) UNSIGNED NOT NULL COMMENT '过期时间',
  `created_at` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  `jti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_token`(`token` ASC) USING BTREE,
  INDEX `idx_admin`(`admin_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 45 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '管理员令牌表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_token
-- ----------------------------
INSERT INTO `admin_token` VALUES (1, 1, '770257636b1c48b515092c0075b34387d011e89276c692698bba16f4cf3349a7', 'admin', NULL, 1751079939, 1750475139, '');
INSERT INTO `admin_token` VALUES (2, 1, '42d1f5ec2950de450129984195b1accaaf717dc6d2b0ced234ef67c4cab3280c', 'admin', NULL, 1751080331, 1750475531, '');
INSERT INTO `admin_token` VALUES (3, 1, '4cf848162f4a7e1ee0e92918dd99cae370d208890d4f18afbee01565e74ff4c3', 'admin', NULL, 1751080370, 1750475570, '');
INSERT INTO `admin_token` VALUES (4, 1, 'c426faa89a60034b3ff439a47afdd38ebf3ab42f3c2bc8af07ba6dcaa1e0013a', 'admin', NULL, 1751081861, 1750477061, '');
INSERT INTO `admin_token` VALUES (5, 1, 'faead12cc3f41d9d1b0d448de5f06626541b9fb7aada46aea905594168498edd', 'admin', NULL, 1751081906, 1750477106, '');
INSERT INTO `admin_token` VALUES (6, 1, '36a81b1b0ca00be3d4623ed96c28b3640e3ce606a540b26642ce0a00565f4cb3', 'admin', NULL, 1751085246, 1750480446, '');
INSERT INTO `admin_token` VALUES (7, 1, 'b8bcbc1b46406ab58d17385bbffe8239e0dd2268bfeccec9dc8b78637e9b8611', 'admin', NULL, 1751085823, 1750481023, 'admin_6856387fdedde5.88582254');
INSERT INTO `admin_token` VALUES (8, 1, 'e440e5596b845a84acd4bde2ce56e81fdcae0d08ffaff11722442c78f5404681', 'admin', NULL, 1751184658, 1750579858, 'admin_6857ba92f13d94.09697488');
INSERT INTO `admin_token` VALUES (9, 1, 'aa1335ad5a5202103747cc9491934f1f5644b4cd2254582c1b42137310969cea', 'admin', NULL, 1751200801, 1750596001, 'admin_6857f9a106fec3.98150215');
INSERT INTO `admin_token` VALUES (10, 1, '3f24b0f2fcda73a60b0a2492dac25a7f1324d0811494629aa213e7c18f9832cf', 'admin', NULL, 1751204643, 1750599843, 'admin_685808a3926be7.89447698');
INSERT INTO `admin_token` VALUES (11, 1, '3000a5bea1cd3d642559929cb5ce40dc4daddea5ef272a5c2b2f814d9a402fc9', 'admin', NULL, 1751204718, 1750599918, 'admin_685808ee202525.03382920');
INSERT INTO `admin_token` VALUES (15, 1, 'de94f4e8d464ae44936e1eae720b9eb8fff29b36b2252e6248cc1d8e4d2c2b18', 'admin', NULL, 1751213290, 1750608490, 'admin_68582a6ab38303.82802241');
INSERT INTO `admin_token` VALUES (16, 1, '40f79bb9335119bb9d8c70fd29425a566b375483e81cd363cb8a41bf0c7bf396', 'admin', NULL, 1751225134, 1750620334, 'admin_685858ae2ddf59.31043594');
INSERT INTO `admin_token` VALUES (17, 1, '384a40055a16f5a9937e3a2d685957c2582ab1e9cb88757def0fa1226528c61e', 'admin', NULL, 1751226036, 1750621236, 'admin_68585c34ec36e0.77251612');
INSERT INTO `admin_token` VALUES (18, 1, '155f9e9d5137b5f0ddbdb5587993d7241247fdb56b23caaa44feb9de56720424', 'admin', NULL, 1751226296, 1750621496, 'admin_68585d38f2cda5.77937875');
INSERT INTO `admin_token` VALUES (19, 1, '4ee4ae3442e139f6da197a16bd83dbef6240baae72c10cd5f50df8d176da0842', 'admin', NULL, 1751226301, 1750621501, 'admin_68585d3d3d0780.19482142');
INSERT INTO `admin_token` VALUES (20, 1, 'f5dcb73c353005361f630edb847072c61a8ffcb7242cedf7fcac712f3635d6d0', 'admin', NULL, 1751226396, 1750621596, 'admin_68585d9c79ba71.10485068');
INSERT INTO `admin_token` VALUES (21, 1, '28f238ddbd1ac573cb657ecbaf096518d6b2f3e27dfcc14306dc9c3c18ebb2dc', 'admin', NULL, 1751964226, 1751359426, 'admin_68639fc2869b81.47662191');
INSERT INTO `admin_token` VALUES (22, 1, '84915c2d6ad5ceb7f7516f4b2e82c4182cf404cd0c8f552c69c527c526b385d6', 'admin', NULL, 1751966410, 1751361610, 'admin_6863a84adee8a0.17750090');
INSERT INTO `admin_token` VALUES (23, 1, '04d15f0102c07349d08b4fc8e4af3d31304d999dd9459d912fb5fa6239fcb702', 'admin', NULL, 1751978581, 1751373781, 'admin_6863d7d5d19cf9.68792535');
INSERT INTO `admin_token` VALUES (24, 1, '7f9fe1ec807e59c80a67778d2eda77334f2a32eeae59d463ab041e2cd89e41e4', 'admin', NULL, 1751990827, 1751386027, 'admin_686407ab79b3a3.30501047');
INSERT INTO `admin_token` VALUES (25, 1, 'bff769711052984d20397f8ad59863d1ae779ae41240c72150785d4f5574b9be', 'admin', NULL, 1752040858, 1751436058, 'admin_6864cb1a964042.31422548');
INSERT INTO `admin_token` VALUES (26, 1, '2f026b11e766772f3f0756c02aef31eb42076be95d2d7b60b3d1ca780d5465ed', 'admin', NULL, 1753231242, 1752626442, 'admin_6876f50aa238a0.72417905');
INSERT INTO `admin_token` VALUES (27, 1, '8e17a772dca1cb3d1b3700872ba4e7f9ac78c626cfd3b6c76a0b526a57caa53d', 'admin', NULL, 1753237401, 1752632601, 'admin_68770d198b8576.94648570');
INSERT INTO `admin_token` VALUES (28, 1, '7aba75aa52b669b526837d20d827af888a7b326d5ee129147868b83d6baa2261', 'admin', NULL, 1753533840, 1752929040, 'admin_687b9310de3223.81654341');
INSERT INTO `admin_token` VALUES (29, 1, '4b8c88f59c285a7a684c35655f7dccf7e6ba450c681e9f126752e269b3b6bf0f', 'admin', NULL, 1753533847, 1752929047, 'admin_687b931758dc79.40622329');
INSERT INTO `admin_token` VALUES (30, 1, '2af33c2662876066826bf3e01146ee3783901c6ca76e31f44aed1f381a1a8472', 'admin', NULL, 1753533929, 1752929129, 'admin_687b9369ada6e2.91347269');
INSERT INTO `admin_token` VALUES (31, 1, '36e71cd4ae7912c40adc23f6734128c3416e8d9e6ee15fb42abd14601cb697e0', 'admin', NULL, 1753533972, 1752929172, 'admin_687b9394597310.92464334');
INSERT INTO `admin_token` VALUES (32, 1, 'd23afdf334e79d5c554db72d728fde718d377d13de38c2f75b462b4643a13c1a', 'admin', NULL, 1753534032, 1752929232, 'admin_687b93d02ec1e1.19233063');
INSERT INTO `admin_token` VALUES (33, 1, '2dc6f9c15d5008c23053f93b145de12d4f2547b91b327e1d942f36ea1f8beb4c', 'admin', NULL, 1753534100, 1752929300, 'admin_687b94142d1993.20546309');
INSERT INTO `admin_token` VALUES (34, 1, 'fee16c5bf363f03c49182d2b5f0db6718784e9cf96ce138fffd2dfa09b2d6ee7', 'admin', NULL, 1753534278, 1752929478, 'admin_687b94c68963f7.46923533');
INSERT INTO `admin_token` VALUES (35, 1, '60e1041bc025fb7df0361f1e0189393bd96fadabd1697ca152180ecb32549081', 'admin', NULL, 1753534287, 1752929487, 'admin_687b94cf2047c9.16502974');
INSERT INTO `admin_token` VALUES (36, 1, '7912d562c287b4b509dc3067c7aefd36eec3303d8dd77d717b548d2df0ccc5dc', 'admin', NULL, 1753534290, 1752929490, 'admin_687b94d2441af0.87373447');
INSERT INTO `admin_token` VALUES (37, 1, '72fc9e43ec7d072a7109050941de3c78a2600e9897cbe747ffb8526c35e0cd02', 'admin', NULL, 1753534310, 1752929510, 'admin_687b94e6d68d47.01016560');
INSERT INTO `admin_token` VALUES (38, 1, 'ceb019309689a5bcc1dc00a140472c6eaaa71be03c003a43746131a96953db35', 'admin', NULL, 1753534354, 1752929554, 'admin_687b95124d0288.38042105');
INSERT INTO `admin_token` VALUES (40, 1, 'a900064d9069a29b8c8310a89c381a919c283b427c8ccc85894175913fe3fe58', 'admin', NULL, 1753756178, 1753151378, 'admin_687ef79255fb88.92565485');
INSERT INTO `admin_token` VALUES (42, 1, '1cc64b5e13a6039846da23413f4a75af795c2e7f700be4c2e05a3789e1afaf7e', 'admin', NULL, 1753758242, 1753153442, 'admin_687effa289c902.11257864');
INSERT INTO `admin_token` VALUES (44, 1, '6de31e441ab7a0c655ebdf024ad7af561666d84598feb45fc22792982dc8693e', 'admin', NULL, 1753758334, 1753153534, 'admin_687efffe49c546.10390999');

-- ----------------------------
-- Table structure for file
-- ----------------------------
DROP TABLE IF EXISTS `file`;
CREATE TABLE `file`  (
  `file_id` int(36) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件唯一标识',
  `origin_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '原始文件名',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '存储文件名',
  `size` bigint(20) NOT NULL COMMENT '文件大小（字节）',
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '文件MIME类型',
  `storage_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '存储类型（local/aliyun_oss/qcloud_cos/aws_s3等）',
  `storage_region` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '存储区域',
  `storage_bucket` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '存储桶名称',
  `storage_path` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '存储路径',
  `access_domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '访问域名',
  `url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '访问URL',
  `storage_permission` enum('private','public') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'public' COMMENT '存储权限（public/private）',
  `file_version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT '1.0' COMMENT '文件版本',
  `status` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '1' COMMENT '文件状态（active/deleted/uploading/expired）',
  `uploader_type` enum('system','admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'admin' COMMENT '上传者类型',
  `uploader_id` int(36) NOT NULL COMMENT '上传者ID',
  `local_server_id` int(50) NULL DEFAULT NULL COMMENT '本地存储服务器ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`file_id`) USING BTREE,
  INDEX `idx_uploader`(`uploader_type` ASC, `uploader_id` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_storage_type`(`storage_type` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '文件存储表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of file
-- ----------------------------
INSERT INTO `file` VALUES (1, '07be39d2-b9d0-434b-98e9-d09f91d3eb4a.png', 'cae7058ab4c6cbb4bbe7deac5231538b.png', 37394, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\cae7058ab4c6cbb4bbe7deac5231538b.png', 'http://127.0.0.1:8000', 'http://127.0.0.1:8000/storage/uploads/20250721\\7ad390c9889fcf22616dc5ba96853f3d.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 16:31:33', '2025-07-21 18:22:35', NULL);
INSERT INTO `file` VALUES (2, '07be39d2-b9d0-434b-98e9-d09f91d3eb4a.png', '7ad390c9889fcf22616dc5ba96853f3d.png', 37394, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\7ad390c9889fcf22616dc5ba96853f3d.png', 'http://127.0.0.1:8000', 'http://127.0.0.1:8000/storage/uploads/20250721\\7ad390c9889fcf22616dc5ba96853f3d.png', 'public', '1.0', '2', 'admin', 1, NULL, '2025-07-21 16:36:45', '2025-07-21 16:43:02', NULL);
INSERT INTO `file` VALUES (3, '07be39d2-b9d0-434b-98e9-d09f91d3eb4a.png', '032ec4a021c2554ee0f9985cea004916.png', 37394, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\032ec4a021c2554ee0f9985cea004916.png', 'http://127.0.0.1:8000', 'http://127.0.0.1:8000/storage/uploads/20250721\\032ec4a021c2554ee0f9985cea004916.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 16:44:09', '2025-07-21 16:44:09', NULL);
INSERT INTO `file` VALUES (4, '3.png', '52b6cd3dafad3963c761344c55ffd590.png', 104045, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\52b6cd3dafad3963c761344c55ffd590.png', 'http://localhost:8000', 'http://localhost:8000/storage/uploads/20250721\\52b6cd3dafad3963c761344c55ffd590.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 18:11:29', '2025-07-21 18:11:29', NULL);
INSERT INTO `file` VALUES (5, '2.png', '73b04d07327f5024363901f13338fa9d.png', 99997, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\73b04d07327f5024363901f13338fa9d.png', 'http://localhost:8000', 'http://localhost:8000/storage/uploads/20250721\\73b04d07327f5024363901f13338fa9d.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 18:20:31', '2025-07-21 18:20:31', NULL);
INSERT INTO `file` VALUES (6, '3.png', '2051a22f230ea58e96163bc29926ecd0.png', 104045, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\2051a22f230ea58e96163bc29926ecd0.png', 'http://localhost:8000', 'http://localhost:8000/storage/uploads/20250721\\2051a22f230ea58e96163bc29926ecd0.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 18:21:17', '2025-07-21 18:21:17', NULL);
INSERT INTO `file` VALUES (7, 'bg-combination.png', '5ff0656cfff7bb416c9d44dd34a5fce7.png', 3700, 'image/png', 'local', NULL, NULL, 'uploads/20250721\\5ff0656cfff7bb416c9d44dd34a5fce7.png', 'http://localhost:8000', 'http://localhost:8000/storage/uploads/20250721\\5ff0656cfff7bb416c9d44dd34a5fce7.png', 'public', '1.0', '1', 'admin', 1, NULL, '2025-07-21 21:09:41', '2025-07-21 21:09:41', NULL);

-- ----------------------------
-- Table structure for menu
-- ----------------------------
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu`  (
  `menu_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '前端页面name',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '菜单图标',
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单ID(0=顶级菜单)',
  `order_num` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序号',
  `visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否可见',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_link` tinyint(4) NOT NULL DEFAULT 2 COMMENT '是否外部链接',
  `is_full` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '是否全屏',
  `is_affix` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '是否affix',
  `is_keep_alive` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否保持活性',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '标题',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '前端页面路径',
  `link_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '链接到哪里',
  `redirect` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '跳转到哪里',
  PRIMARY KEY (`menu_id`) USING BTREE,
  INDEX `fk_parent`(`parent_id` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 72 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '菜单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of menu
-- ----------------------------
INSERT INTO `menu` VALUES (1, 'home', 'HomeFilled', 0, 0, 1, '2025-06-23 00:00:00', '2025-07-01 23:56:27', 2, 2, 1, 1, '首页', '/home/index', '', '');
INSERT INTO `menu` VALUES (57, 'system', 'Tools', 0, 10, 1, '2025-06-23 00:00:00', '2025-07-01 23:56:42', 2, 2, 2, 1, '系统管理', '', '', '/system/accountManage');
INSERT INTO `menu` VALUES (58, 'accountManage', 'UserFilled', 57, 0, 1, '2025-06-23 00:00:00', '2025-07-21 23:17:51', 2, 2, 2, 1, '账号管理', '/system/accountManage/index', '', '');
INSERT INTO `menu` VALUES (59, 'roleManage', 'WarningFilled', 57, 1, 1, '2025-06-23 00:00:00', '2025-07-21 23:19:45', 2, 2, 2, 1, '角色管理', '/system/roleManage/index', '', '');
INSERT INTO `menu` VALUES (60, 'menuMange', 'Menu', 57, 2, 1, '2025-06-23 00:00:00', '2025-07-01 23:57:32', 2, 2, 2, 1, '菜单管理', '/system/menuMange/index', '', '');
INSERT INTO `menu` VALUES (61, 'departmentManage', 'Menu', 57, 3, 1, '2025-06-23 00:00:00', '2025-07-18 09:13:57', 2, 2, 2, 1, '部门管理', '/system/departmentManage/index', '', '');
INSERT INTO `menu` VALUES (62, 'dictManage', 'Menu', 57, 4, 1, '2025-06-23 00:00:00', '2025-07-18 09:13:59', 2, 2, 2, 1, '字典管理', '/system/dictManage/index', '', '');
INSERT INTO `menu` VALUES (63, 'timingTask', 'Menu', 57, 5, 1, '2025-06-23 00:00:00', '2025-07-18 09:14:01', 2, 2, 2, 1, '定时任务', '/system/timingTask/index', '', '');
INSERT INTO `menu` VALUES (64, 'systemLog', 'Menu', 57, 6, 1, '2025-06-23 00:00:00', '2025-06-23 00:00:00', 2, 2, 2, 1, '系统日志', '/system/systemLog/index', '', '');
INSERT INTO `menu` VALUES (65, 'permissionManage', 'Menu', 57, 4, 1, '2025-06-23 00:00:00', '2025-07-16 18:28:39', 2, 2, 2, 1, '权限管理', '/system/permissionManage/index', '', '');
INSERT INTO `menu` VALUES (71, 'about', 'InfoFilled', 0, 12, 1, '2025-06-23 00:00:00', '2025-06-23 00:00:00', 2, 2, 2, 1, '关于项目', '/about/index', '', '');

-- ----------------------------
-- Table structure for menu_permission_dependency
-- ----------------------------
DROP TABLE IF EXISTS `menu_permission_dependency`;
CREATE TABLE `menu_permission_dependency`  (
  `dependency_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '依赖ID',
  `menu_id` int(10) UNSIGNED NOT NULL COMMENT '菜单ID',
  `permission_id` int(10) UNSIGNED NOT NULL COMMENT '权限ID',
  `type` enum('REQUIRED','OPTIONAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'REQUIRED' COMMENT '依赖类型REQUIRED 必备项,OPTIONAL 可选项',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_type` enum('data','search','button') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT 'data' COMMENT '节点类型 data 页面主数据 search 搜索项  button 按钮',
  PRIMARY KEY (`dependency_id`) USING BTREE,
  UNIQUE INDEX `uq_menu_permission`(`menu_id` ASC, `permission_id` ASC) USING BTREE,
  INDEX `fk_menu`(`menu_id` ASC) USING BTREE,
  INDEX `fk_permission`(`permission_id` ASC) USING BTREE,
  CONSTRAINT `fk_mpd_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_mpd_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 42 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '菜单-权限依赖关系' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of menu_permission_dependency
-- ----------------------------
INSERT INTO `menu_permission_dependency` VALUES (1, 57, 5, 'REQUIRED', '系统设置查看权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (2, 57, 9, 'REQUIRED', '权限管理查看权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (3, 57, 14, 'REQUIRED', '菜单管理查看权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (4, 57, 19, 'REQUIRED', '管理员查看权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (15, 62, 6, 'REQUIRED', '系统配置提交权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (16, 62, 7, 'REQUIRED', '基础配置访问权限', '2025-07-01 00:00:00', 'data');
INSERT INTO `menu_permission_dependency` VALUES (17, 65, 11, 'REQUIRED', '新增', '2025-07-20 10:02:05', 'button');
INSERT INTO `menu_permission_dependency` VALUES (18, 65, 12, 'REQUIRED', '编辑', '2025-07-20 10:02:05', 'button');
INSERT INTO `menu_permission_dependency` VALUES (19, 65, 13, 'REQUIRED', '删除', '2025-07-20 10:02:33', 'button');
INSERT INTO `menu_permission_dependency` VALUES (29, 60, 15, 'REQUIRED', NULL, '2025-07-20 22:27:43', 'data');
INSERT INTO `menu_permission_dependency` VALUES (30, 60, 16, 'OPTIONAL', NULL, '2025-07-20 22:27:43', 'button');
INSERT INTO `menu_permission_dependency` VALUES (31, 60, 17, 'OPTIONAL', NULL, '2025-07-20 22:27:43', 'button');
INSERT INTO `menu_permission_dependency` VALUES (32, 60, 18, 'OPTIONAL', NULL, '2025-07-20 22:27:43', 'button');
INSERT INTO `menu_permission_dependency` VALUES (33, 60, 14, 'OPTIONAL', NULL, '2025-07-20 22:27:43', 'button');
INSERT INTO `menu_permission_dependency` VALUES (35, 58, 19, 'OPTIONAL', NULL, '2025-07-21 23:17:51', 'button');
INSERT INTO `menu_permission_dependency` VALUES (36, 58, 20, 'REQUIRED', NULL, '2025-07-21 23:17:51', 'data');
INSERT INTO `menu_permission_dependency` VALUES (37, 58, 21, 'REQUIRED', NULL, '2025-07-21 23:17:51', 'button');
INSERT INTO `menu_permission_dependency` VALUES (38, 58, 22, 'REQUIRED', NULL, '2025-07-21 23:17:51', 'button');
INSERT INTO `menu_permission_dependency` VALUES (39, 58, 23, 'REQUIRED', NULL, '2025-07-21 23:17:51', 'button');
INSERT INTO `menu_permission_dependency` VALUES (40, 59, 9, 'REQUIRED', NULL, '2025-07-21 23:19:45', 'data');
INSERT INTO `menu_permission_dependency` VALUES (41, 59, 10, 'REQUIRED', NULL, '2025-07-21 23:19:45', 'data');

-- ----------------------------
-- Table structure for permission
-- ----------------------------
DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission`  (
  `permission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `node` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '权限唯一标识(如user:list)',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '权限名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '权限描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `method` enum('post','get','delete','put') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '方法类型',
  `is_public` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '是否免权限验证',
  PRIMARY KEY (`permission_id`) USING BTREE,
  UNIQUE INDEX `idx_name`(`name` ASC) USING BTREE,
  UNIQUE INDEX `idx_identifier`(`node` ASC, `method` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '权限节点表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of permission
-- ----------------------------
INSERT INTO `permission` VALUES (1, 'Login/doLogin', '用户登录', NULL, '2025-06-20 15:51:47', '2025-07-16 16:57:49', 'post', 1);
INSERT INTO `permission` VALUES (2, 'Login/index', '获取验证码', NULL, '2025-06-20 15:51:47', '2025-07-16 16:57:53', 'get', 1);
INSERT INTO `permission` VALUES (3, 'index/dashboard', '首页看板', NULL, '2025-06-20 15:51:47', '2025-07-19 20:49:32', 'get', 1);
INSERT INTO `permission` VALUES (4, 'my/password', '修改自己的密码', NULL, '2025-06-20 15:51:47', '2025-07-16 16:57:59', 'post', 1);
INSERT INTO `permission` VALUES (5, 'system/view', '查看系统设置', '查看系统设置菜单', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'get', 2);
INSERT INTO `permission` VALUES (6, 'system/submit', '提交系统设置', '保存所有系统配置', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'post', 2);
INSERT INTO `permission` VALUES (7, 'system/tab1', '基础配置访问', '访问基础配置tab', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'get', 2);
INSERT INTO `permission` VALUES (8, 'system/tab2', '安全配置访问', '访问安全配置tab', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'get', 2);
INSERT INTO `permission` VALUES (9, 'permission/read', '查看权限管理', '显示权限管理菜单', '2025-07-01 00:00:00', '2025-07-16 18:21:31', 'get', 2);
INSERT INTO `permission` VALUES (10, 'permission/index', '权限列表查询', '查询权限节点列表', '2025-07-01 00:00:00', '2025-07-16 16:51:12', 'get', 2);
INSERT INTO `permission` VALUES (11, 'permission/create', '创建权限节点', '新增权限节点', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'post', 2);
INSERT INTO `permission` VALUES (12, 'permission/update', '修改权限节点', '编辑权限节点', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'put', 2);
INSERT INTO `permission` VALUES (13, 'permission/delete', '删除权限节点', '删除权限节点', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'delete', 2);
INSERT INTO `permission` VALUES (14, 'menu/read', '查看菜单管理', '显示菜单管理菜单', '2025-07-01 00:00:00', '2025-07-16 18:21:34', 'get', 2);
INSERT INTO `permission` VALUES (15, 'menu/tree', '菜单列表查询', '查询菜单树结构', '2025-07-01 00:00:00', '2025-07-16 16:50:34', 'get', 2);
INSERT INTO `permission` VALUES (16, 'menu/create', '创建菜单', '新增菜单项', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'post', 2);
INSERT INTO `permission` VALUES (17, 'menu/update', '修改菜单', '编辑菜单项', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'put', 2);
INSERT INTO `permission` VALUES (18, 'menu/delete', '删除菜单', '删除菜单项', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'delete', 2);
INSERT INTO `permission` VALUES (19, 'admin/read', '查看管理员', '显示管理员菜单', '2025-07-01 00:00:00', '2025-07-16 18:21:38', 'get', 2);
INSERT INTO `permission` VALUES (20, 'admin/index', '管理员列表', '查询管理员列表', '2025-07-01 00:00:00', '2025-07-16 18:28:53', 'get', 2);
INSERT INTO `permission` VALUES (21, 'admin/create', '创建管理员', '新增管理员账号', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'post', 2);
INSERT INTO `permission` VALUES (22, 'admin/update', '修改管理员', '编辑管理员信息', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'put', 2);
INSERT INTO `permission` VALUES (23, 'admin/delete', '删除管理员', '删除管理员账号', '2025-07-01 00:00:00', '2025-07-01 00:00:00', 'delete', 2);

-- ----------------------------
-- Table structure for role
-- ----------------------------
DROP TABLE IF EXISTS `role`;
CREATE TABLE `role`  (
  `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '角色名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '角色描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`) USING BTREE,
  UNIQUE INDEX `idx_name`(`name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of role
-- ----------------------------

-- ----------------------------
-- Table structure for role_menu
-- ----------------------------
DROP TABLE IF EXISTS `role_menu`;
CREATE TABLE `role_menu`  (
  `role_id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(11) NOT NULL
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '角色拥有的菜单' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of role_menu
-- ----------------------------

-- ----------------------------
-- Table structure for role_permission
-- ----------------------------
DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE `role_permission`  (
  `role_id` int(10) UNSIGNED NOT NULL COMMENT '角色ID',
  `permission_id` int(10) UNSIGNED NOT NULL COMMENT '权限ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_id` int(11) NOT NULL COMMENT '从哪里来',
  PRIMARY KEY (`role_id`, `permission_id`) USING BTREE,
  INDEX `fk_role_permission_permission`(`permission_id` ASC) USING BTREE,
  CONSTRAINT `fk_role_permission_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_role_permission_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '角色-权限关联' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of role_permission
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
