<?php

namespace common\api\br;

use common\models\CreditBr;


class Brlist
{
    public $specialList_c = [
        'sl_id_abnormal' => '通过身份证号查询高危行为',
        'sl_id_phone_overdue' => '通过身份证号查询电信欠费',
        'sl_id_court_bad' => '通过身份证号查询法院失信人',
        'sl_id_court_executed' => '通过身份证号查询法院被执行人',
        'sl_id_bank_bad' => '通过身份证号查询银行(含信用卡)不良',
        'sl_id_bank_overdue' => '通过身份证号查询银行(含信用卡)短时逾期',
        'sl_id_bank_fraud' => '通过身份证号查询银行(含信用卡)资信不佳',
        'sl_id_bank_lost' => '通过身份证号查询银行(含信用卡)失联',
        'sl_id_bank_refuse' => '通过身份证号查询银行(含信用卡)拒绝',
        'sl_id_p2p_bad' => '通过身份证号查询非银(含全部非银类型)不良',
        'sl_id_p2p_overdue' => '通过身份证号查询非银(含全部非银类型)短时逾期',
        'sl_id_p2p_fraud' => '通过身份证号查询非银(含全部非银类型)资信不佳',
        'sl_id_p2p_lost' => '通过身份证号查询非银(含全部非银类型)失联',
        'sl_id_p2p_refuse' => '通过身份证号查询非银(含全部非银类型)拒绝',
        'sl_id_nbank_p2p_bad' => '通过身份证号查询非银-P2P不良',
        'sl_id_nbank_p2p_overdue' => '通过身份证号查询非银-P2P短时逾期',
        'sl_id_nbank_p2p_fraud' => '通过身份证号查询非银-P2P资信不佳',
        'sl_id_nbank_p2p_lost' => '通过身份证号查询非银-P2P失联',
        'sl_id_nbank_p2p_refuse' => '通过身份证号查询非银-P2P拒绝',
        'sl_id_nbank_mc_bad' => '通过身份证号查询非银-小贷不良',
        'sl_id_nbank_mc_overdue' => '通过身份证号查询非银-小贷短时逾期',
        'sl_id_nbank_mc_fraud' => '通过身份证号查询非银-小贷资信不佳',
        'sl_id_nbank_mc_lost' => '通过身份证号查询非银-小贷失联',
        'sl_id_nbank_mc_refuse' => '通过身份证号查询非银-小贷拒绝',
        'sl_id_nbank_ca_bad' => '通过身份证号查询非银-现金类分期不良',
        'sl_id_nbank_ca_overdue' => '通过身份证号查询非银-现金类分期短时逾期',
        'sl_id_nbank_ca_fraud' => '通过身份证号查询非银-现金类分期资信不佳',
        'sl_id_nbank_ca_lost' => '通过身份证号查询非银-现金类分期失联',
        'sl_id_nbank_ca_refuse' => '通过身份证号查询非银-现金类分期拒绝',
        'sl_id_nbank_com_bad' => '通过身份证号查询非银-代偿类分期不良',
        'sl_id_nbank_com_overdue' => '通过身份证号查询非银-代偿类分期短时逾期',
        'sl_id_nbank_com_fraud' => '通过身份证号查询非银-代偿类分期资信不佳',
        'sl_id_nbank_com_lost' => '通过身份证号查询非银-代偿类分期失联',
        'sl_id_nbank_com_refuse' => '通过身份证号查询非银-代偿类分期拒绝',
        'sl_id_nbank_cf_bad' => '通过身份证号查询非银-消费类分期不良',
        'sl_id_nbank_cf_overdue' => '通过身份证号查询非银-消费类分期短时逾期',
        'sl_id_nbank_cf_fraud' => '通过身份证号查询非银-消费类分期资信不佳',
        'sl_id_nbank_cf_lost' => '通过身份证号查询非银-消费类分期失联',
        'sl_id_nbank_cf_refuse' => '通过身份证号查询非银-消费类分期拒绝',
        'sl_id_nbank_other_bad' => '通过身份证号查询非银-其他不良',
        'sl_id_nbank_other_overdue' => '通过身份证号查询非银-其他短时逾期',
        'sl_id_nbank_other_fraud' => '通过身份证号查询非银-其他资信不佳',
        'sl_id_nbank_other_lost' => '通过身份证号查询非银-其他失联',
        'sl_id_nbank_other_refuse' => '通过身份证号查询非银-其他拒绝',
        'sl_cell_abnormal' => '通过手机号查询高危行为',
        'sl_cell_phone_overdue' => '通过手机号查询电信欠费',
        'sl_cell_bank_bad' => '通过手机号查询银行(含信用卡)不良',
        'sl_cell_bank_overdue' => '通过手机号查询银行(含信用卡)短时逾期',
        'sl_cell_bank_fraud' => '通过手机号查询银行(含信用卡)资信不佳',
        'sl_cell_bank_lost' => '通过手机号查询银行(含信用卡)失联',
        'sl_cell_bank_refuse' => '通过手机号查询银行(含信用卡)拒绝',
        'sl_cell_p2p_bad' => '通过手机号查询非银(含全部非银类型)不良',
        'sl_cell_p2p_overdue' => '通过手机号查询非银(含全部非银类型)短时逾期',
        'sl_cell_p2p_fraud' => '通过手机号查询非银(含全部非银类型)资信不佳',
        'sl_cell_p2p_lost' => '通过手机号查询非银(含全部非银类型)失联',
        'sl_cell_p2p_refuse' => '通过手机号查询非银(含全部非银类型)拒绝',
        'sl_cell_nbank_p2p_bad' => '通过手机号查询非银-P2P不良',
        'sl_cell_nbank_p2p_overdue' => '通过手机号查询非银-P2P短时逾期',
        'sl_cell_nbank_p2p_fraud' => '通过手机号查询非银-P2P资信不佳',
        'sl_cell_nbank_p2p_lost' => '通过手机号查询非银-P2P失联',
        'sl_cell_nbank_p2p_refuse' => '通过手机号查询非银-P2P拒绝',
        'sl_cell_nbank_mc_bad' => '通过手机号查询非银-小贷不良',
        'sl_cell_nbank_mc_overdue' => '通过手机号查询非银-小贷短时逾期',
        'sl_cell_nbank_mc_fraud' => '通过手机号查询非银-小贷资信不佳',
        'sl_cell_nbank_mc_lost' => '通过手机号查询非银-小贷失联',
        'sl_cell_nbank_mc_refuse' => '通过手机号查询非银-小贷拒绝',
        'sl_cell_nbank_ca_bad' => '通过手机号查询非银-现金类分期不良',
        'sl_cell_nbank_ca_overdue' => '通过手机号查询非银-现金类分期短时逾期',
        'sl_cell_nbank_ca_fraud' => '通过手机号查询非银-现金类分期资信不佳',
        'sl_cell_nbank_ca_lost' => '通过手机号查询非银-现金类分期失联',
        'sl_cell_nbank_ca_refuse' => '通过手机号查询非银-现金类分期拒绝',
        'sl_cell_nbank_com_bad' => '通过手机号查询非银-代偿类分期不良',
        'sl_cell_nbank_com_overdue' => '通过手机号查询非银-代偿类分期短时逾期',
        'sl_cell_nbank_com_fraud' => '通过手机号查询非银-代偿类分期资信不佳',
        'sl_cell_nbank_com_lost' => '通过手机号查询非银-代偿类分期失联',
        'sl_cell_nbank_com_refuse' => '通过手机号查询非银-代偿类分期拒绝',
        'sl_cell_nbank_cf_bad' => '通过手机号查询非银-消费类分期不良',
        'sl_cell_nbank_cf_overdue' => '通过手机号查询非银-消费类分期短时逾期',
        'sl_cell_nbank_cf_fraud' => '通过手机号查询非银-消费类分期资信不佳',
        'sl_cell_nbank_cf_lost' => '通过手机号查询非银-消费类分期失联',
        'sl_cell_nbank_cf_refuse' => '通过手机号查询非银-消费类分期拒绝',
        'sl_cell_nbank_other_bad' => '通过手机号查询非银-其他不良',
        'sl_cell_nbank_other_overdue' => '通过手机号查询非银-其他短时逾期',
        'sl_cell_nbank_other_fraud' => '通过手机号查询非银-其他资信不佳',
        'sl_cell_nbank_other_lost' => '通过手机号查询非银-其他失联',
        'sl_cell_nbank_other_refuse' => '通过手机号查询非银-其他拒绝',
        'sl_lm_cell_abnormal' => '通过联系人手机查询高危行为',
        'sl_lm_cell_phone_overdue' => '通过联系人手机查询电信欠费',
        'sl_lm_cell_bank_bad' => '通过联系人手机查询银行(含信用卡)不良',
        'sl_lm_cell_bank_overdue' => '通过联系人手机查询银行(含信用卡)短时逾期',
        'sl_lm_cell_bank_fraud' => '通过联系人手机查询银行(含信用卡)资信不佳',
        'sl_lm_cell_bank_lost' => '通过联系人手机查询银行(含信用卡)失联',
        'sl_lm_cell_bank_refuse' => '通过联系人手机查询银行(含信用卡)拒绝',
        'sl_lm_cell_nbank_p2p_bad' => '通过联系人手机查询非银-P2P不良',
        'sl_lm_cell_nbank_p2p_overdue' => '通过联系人手机查询非银-P2P短时逾期',
        'sl_lm_cell_nbank_p2p_fraud' => '通过联系人手机查询非银-P2P资信不佳',
        'sl_lm_cell_nbank_p2p_lost' => '通过联系人手机查询非银-P2P失联',
        'sl_lm_cell_nbank_p2p_refuse' => '通过联系人手机查询非银-P2P拒绝',
        'sl_lm_cell_nbank_mc_bad' => '通过联系人手机查询非银-小贷不良',
        'sl_lm_cell_nbank_mc_overdue' => '通过联系人手机查询非银-小贷短时逾期',
        'sl_lm_cell_nbank_mc_fraud' => '通过联系人手机查询非银-小贷资信不佳',
        'sl_lm_cell_nbank_mc_lost' => '通过联系人手机查询非银-小贷失联',
        'sl_lm_cell_nbank_mc_refuse' => '通过联系人手机查询非银-小贷拒绝',
        'sl_lm_cell_nbank_ca_bad' => '通过联系人手机查询非银-现金类分期不良',
        'sl_lm_cell_nbank_ca_overdue' => '通过联系人手机查询非银-现金类分期短时逾期',
        'sl_lm_cell_nbank_ca_fraud' => '通过联系人手机查询非银-现金类分期资信不佳',
        'sl_lm_cell_nbank_ca_lost' => '通过联系人手机查询非银-现金类分期失联',
        'sl_lm_cell_nbank_ca_refuse' => '通过联系人手机查询非银-现金类分期拒绝',
        'sl_lm_cell_nbank_com_bad' => '通过联系人手机查询非银-代偿类分期不良',
        'sl_lm_cell_nbank_com_overdue' => '通过联系人手机查询非银-代偿类分期短时逾期',
        'sl_lm_cell_nbank_com_fraud' => '通过联系人手机查询非银-代偿类分期资信不佳',
        'sl_lm_cell_nbank_com_lost' => '通过联系人手机查询非银-代偿类分期失联',
        'sl_lm_cell_nbank_com_refuse' => '通过联系人手机查询非银-代偿类分期拒绝',
        'sl_lm_cell_nbank_cf_bad' => '通过联系人手机查询非银-消费类分期不良',
        'sl_lm_cell_nbank_cf_overdue' => '通过联系人手机查询非银-消费类分期短时逾期',
        'sl_lm_cell_nbank_cf_fraud' => '通过联系人手机查询非银-消费类分期资信不佳',
        'sl_lm_cell_nbank_cf_lost' => '通过联系人手机查询非银-消费类分期失联',
        'sl_lm_cell_nbank_cf_refuse' => '通过联系人手机查询非银-消费类分期拒绝',
        'sl_lm_cell_nbank_other_bad' => '通过联系人手机查询非银-其他不良',
        'sl_lm_cell_nbank_other_overdue' => '通过联系人手机查询非银-其他短时逾期',
        'sl_lm_cell_nbank_other_fraud' => '通过联系人手机查询非银-其他资信不佳',
        'sl_lm_cell_nbank_other_lost' => '通过联系人手机查询非银-其他失联',
        'sl_lm_cell_nbank_other_refuse' => '通过联系人手机查询非银-其他拒绝',
        'sl_gid_phone_overdue' => '通过百融标识查询电信欠费',
        'sl_gid_bank_bad' => '通过百融标识查询银行(含信用卡)不良',
        'sl_gid_bank_overdue' => '通过百融标识查询银行(含信用卡)短时逾期',
        'sl_gid_bank_fraud' => '通过百融标识查询银行(含信用卡)资信不佳',
        'sl_gid_bank_lost' => '通过百融标识查询银行(含信用卡)失联',
        'sl_gid_bank_refuse' => '通过百融标识查询银行(含信用卡)拒绝',
        'sl_gid_p2p_bad' => '通过百融标识查询非银(含全部非银类型)不良',
        'sl_gid_p2p_overdue' => '通过百融标识查询非银(含全部非银类型)短时逾期',
        'sl_gid_p2p_fraud' => '通过百融标识查询非银(含全部非银类型)资信不佳',
        'sl_gid_p2p_lost' => '通过百融标识查询非银(含全部非银类型)失联',
        'sl_gid_p2p_refuse' => '通过百融标识查询非银(含全部非银类型)拒绝',
        'sl_gid_nbank_p2p_bad' => '通过百融用户全局标识查询非银-P2P不良',
        'sl_gid_nbank_p2p_overdue' => '通过百融用户全局标识查询非银-P2P短时逾期',
        'sl_gid_nbank_p2p_fraud' => '通过百融用户全局标识查询非银-P2P资信不佳',
        'sl_gid_nbank_p2p_lost' => '通过百融用户全局标识查询非银-P2P失联',
        'sl_gid_nbank_p2p_refuse' => '通过百融用户全局标识查询非银-P2P拒绝',
        'sl_gid_nbank_mc_bad' => '通过百融用户全局标识查询非银-小贷不良',
        'sl_gid_nbank_mc_overdue' => '通过百融用户全局标识查询非银-小贷短时逾期',
        'sl_gid_nbank_mc_fraud' => '通过百融用户全局标识查询非银-小贷资信不佳',
        'sl_gid_nbank_mc_lost' => '通过百融用户全局标识查询非银-小贷失联',
        'sl_gid_nbank_mc_refuse' => '通过百融用户全局标识查询非银-小贷拒绝',
        'sl_gid_nbank_ca_bad' => '通过百融用户全局标识查询非银-现金类分期不良',
        'sl_gid_nbank_ca_overdue' => '通过百融用户全局标识查询非银-现金类分期短时逾期',
        'sl_gid_nbank_ca_fraud' => '通过百融用户全局标识查询非银-现金类分期资信不佳',
        'sl_gid_nbank_ca_lost' => '通过百融用户全局标识查询非银-现金类分期失联',
        'sl_gid_nbank_ca_refuse' => '通过百融用户全局标识查询非银-现金类分期拒绝',
        'sl_gid_nbank_com_bad' => '通过百融用户全局标识查询非银-代偿类分期不良',
        'sl_gid_nbank_com_overdue' => '通过百融用户全局标识查询非银-代偿类分期短时逾期',
        'sl_gid_nbank_com_fraud' => '通过百融用户全局标识查询非银-代偿类分期资信不佳',
        'sl_gid_nbank_com_lost' => '通过百融用户全局标识查询非银-代偿类分期失联',
        'sl_gid_nbank_com_refuse' => '通过百融用户全局标识查询非银-代偿类分期拒绝',
        'sl_gid_nbank_cf_bad' => '通过百融用户全局标识查询非银-消费类分期不良',
        'sl_gid_nbank_cf_overdue' => '通过百融用户全局标识查询非银-消费类分期短时逾期',
        'sl_gid_nbank_cf_fraud' => '通过百融用户全局标识查询非银-消费类分期资信不佳',
        'sl_gid_nbank_cf_lost' => '通过百融用户全局标识查询非银-消费类分期失联',
        'sl_gid_nbank_cf_refuse' => '通过百融用户全局标识查询非银-消费类分期拒绝',
        'sl_gid_nbank_other_bad' => '通过百融用户全局标识查询非银-其他不良',
        'sl_gid_nbank_other_overdue' => '通过百融用户全局标识查询非银-其他短时逾期',
        'sl_gid_nbank_other_fraud' => '通过百融用户全局标识查询非银-其他资信不佳',
        'sl_gid_nbank_other_lost' => '通过百融用户全局标识查询非银-其他失联',
        'sl_gid_nbank_other_refuse' => '通过百融用户全局标识查询非银-其他拒绝'
    ];

    public $applyloanstr = [
        'als_d7_id_bank_selfnum' => '按身份证号查询，近7天在本机构(本机构为银行)的申请次数',
        'als_d7_id_bank_allnum' => '按身份证号查询，近7天在银行机构申请次数',
        'als_d7_id_bank_orgnum' => '按身份证号查询，近7天在银行机构申请机构数',
        'als_d7_id_nbank_selfnum' => '按身份证号查询，近7天在本机构(本机构为非银)申请次数',
        'als_d7_id_nbank_allnum' => '按身份证号查询，近7天在非银机构申请次数',
        'als_d7_id_nbank_p2p_allnum' => '按身份证号查询，近7天在非银机构-p2p申请次数',
        'als_d7_id_nbank_mc_allnum' => '按身份证号查询，近7天在非银机构-小贷申请次数',
        'als_d7_id_nbank_ca_allnum' => '按身份证号查询，近7天在非银机构-现金类分期申请次数',
        'als_d7_id_nbank_cf_allnum' => '按身份证号查询，近7天在非银机构-消费类分期申请次数',
        'als_d7_id_nbank_com_allnum' => '按身份证号查询，近7天在非银机构-代偿类分期申请次数',
        'als_d7_id_nbank_oth_allnum' => '按身份证号查询，近7天在非银机构-其他申请次数',
        'als_d7_id_nbank_orgnum' => '按身份证号查询，近7天在非银机构申请机构数',
        'als_d7_id_nbank_p2p_orgnum' => '按身份证号查询，近7天在非银机构-p2p申请机构数',
        'als_d7_id_nbank_mc_orgnum' => '按身份证号查询，近7天在非银机构-小贷申请机构数',
        'als_d7_id_nbank_ca_orgnum' => '按身份证号查询，近7天在非银机构-现金类分期申请机构数',
        'als_d7_id_nbank_cf_orgnum' => '按身份证号查询，近7天在非银机构-消费类分期申请机构数',
        'als_d7_id_nbank_com_orgnum' => '按身份证号查询，近7天在非银机构-代偿类分期申请机构数',
        'als_d7_id_nbank_oth_orgnum' => '按身份证号查询，近7天在非银机构-其他申请机构数',
        'als_d7_cell_bank_selfnum' => '按手机号查询，近7天在本机构(本机构为银行)的申请次数',
        'als_d7_cell_bank_allnum' => '按手机号查询，近7天在银行机构申请次数',
        'als_d7_cell_bank_orgnum' => '按手机号查询，近7天在银行机构申请机构数',
        'als_d7_cell_nbank_selfnum' => '按手机号查询，近7天在本机构(本机构为非银)申请次数',
        'als_d7_cell_nbank_allnum' => '按手机号查询，近7天在非银机构申请次数',
        'als_d7_cell_nbank_p2p_allnum' => '按手机号查询，近7天在非银机构-p2p申请次数',
        'als_d7_cell_nbank_mc_allnum' => '按手机号查询，近7天在非银机构-小贷申请次数',
        'als_d7_cell_nbank_ca_allnum' => '按手机号查询，近7天在非银机构-现金类分期申请次数',
        'als_d7_cell_nbank_cf_allnum' => '按手机号查询，近7天在非银机构-消费类分期申请次数',
        'als_d7_cell_nbank_com_allnum' => '按手机号查询，近7天在非银机构-代偿类分期申请次数',
        'als_d7_cell_nbank_oth_allnum' => '按手机号查询，近7天在非银机构-其他申请次数',
        'als_d7_cell_nbank_orgnum' => '按手机号查询，近7天在非银机构申请机构数',
        'als_d7_cell_nbank_p2p_orgnum' => '按手机号查询，近7天在非银机构-p2p申请机构数',
        'als_d7_cell_nbank_mc_orgnum' => '按手机号查询，近7天在非银机构-小贷申请机构数',
        'als_d7_cell_nbank_ca_orgnum' => '按手机号查询，近7天在非银机构-现金类分期申请机构数',
        'als_d7_cell_nbank_cf_orgnum' => '按手机号查询，近7天在非银机构-消费类分期申请机构数',
        'als_d7_cell_nbank_com_orgnum' => '按手机号查询，近7天在非银机构-代偿类分期申请机构数',
        'als_d7_cell_nbank_oth_orgnum' => '按手机号查询，近7天在非银机构-其他申请机构数',
        'als_d15_id_bank_selfnum' => '按身份证号查询，近15天在本机构(本机构为银行)的申请次数',
        'als_d15_id_bank_allnum' => '按身份证号查询，近15天在银行机构申请次数',
        'als_d15_id_bank_orgnum' => '按身份证号查询，近15天在银行机构申请机构数',
        'als_d15_id_nbank_selfnum' => '按身份证号查询，近15天在本机构(本机构为非银)申请次数',
        'als_d15_id_nbank_allnum' => '按身份证号查询，近15天在非银机构申请次数',
        'als_d15_id_nbank_p2p_allnum' => '按身份证号查询，近15天在非银机构-p2p申请次数',
        'als_d15_id_nbank_mc_allnum' => '按身份证号查询，近15天在非银机构-小贷申请次数',
        'als_d15_id_nbank_ca_allnum' => '按身份证号查询，近15天在非银机构-现金类分期申请次数',
        'als_d15_id_nbank_cf_allnum' => '按身份证号查询，近15天在非银机构-消费类分期申请次数',
        'als_d15_id_nbank_com_allnum' => '按身份证号查询，近15天在非银机构-代偿类分期申请次数',
        'als_d15_id_nbank_oth_allnum' => '按身份证号查询，近15天在非银机构-其他申请次数',
        'als_d15_id_nbank_orgnum' => '按身份证号查询，近15天在非银机构申请机构数',
        'als_d15_id_nbank_p2p_orgnum' => '按身份证号查询，近15天在非银机构-p2p申请机构数',
        'als_d15_id_nbank_mc_orgnum' => '按身份证号查询，近15天在非银机构-小贷申请机构数',
        'als_d15_id_nbank_ca_orgnum' => '按身份证号查询，近15天在非银机构-现金类分期申请机构数',
        'als_d15_id_nbank_cf_orgnum' => '按身份证号查询，近15天在非银机构-消费类分期申请机构数',
        'als_d15_id_nbank_com_orgnum' => '按身份证号查询，近15天在非银机构-代偿类分期申请机构数',
        'als_d15_id_nbank_oth_orgnum' => '按身份证号查询，近15天在非银机构-其他申请机构数',
        'als_d15_cell_bank_selfnum' => '按手机号查询，近15天在本机构(本机构为银行)的申请次数',
        'als_d15_cell_bank_allnum' => '按手机号查询，近15天在银行机构申请次数',
        'als_d15_cell_bank_orgnum' => '按手机号查询，近15天在银行机构申请机构数',
        'als_d15_cell_nbank_selfnum' => '按手机号查询，近15天在本机构(本机构为非银)申请次数',
        'als_d15_cell_nbank_allnum' => '按手机号查询，近15天在非银机构申请次数',
        'als_d15_cell_nbank_p2p_allnum' => '按手机号查询，近15天在非银机构-p2p申请次数',
        'als_d15_cell_nbank_mc_allnum' => '按手机号查询，近15天在非银机构-小贷申请次数',
        'als_d15_cell_nbank_ca_allnum' => '按手机号查询，近15天在非银机构-现金类分期申请次数',
        'als_d15_cell_nbank_cf_allnum' => '按手机号查询，近15天在非银机构-消费类分期申请次数',
        'als_d15_cell_nbank_com_allnum' => '按手机号查询，近15天在非银机构-代偿类分期申请次数',
        'als_d15_cell_nbank_oth_allnum' => '按手机号查询，近15天在非银机构-其他申请次数',
        'als_d15_cell_nbank_orgnum' => '按手机号查询，近15天在非银机构申请机构数',
        'als_d15_cell_nbank_p2p_orgnum' => '按手机号查询，近15天在非银机构-p2p申请机构数',
        'als_d15_cell_nbank_mc_orgnum' => '按手机号查询，近15天在非银机构-小贷申请机构数',
        'als_d15_cell_nbank_ca_orgnum' => '按手机号查询，近15天在非银机构-现金类分期申请机构数',
        'als_d15_cell_nbank_cf_orgnum' => '按手机号查询，近15天在非银机构-消费类分期申请机构数',
        'als_d15_cell_nbank_com_orgnum' => '按手机号查询，近15天在非银机构-代偿类分期申请机构数',
        'als_d15_cell_nbank_oth_orgnum' => '按手机号查询，近15天在非银机构-其他申请机构数',
        'als_m1_id_bank_selfnum' => '按身份证号查询，近1个月在本机构(本机构为银行)的申请次数',
        'als_m1_id_bank_allnum' => '按身份证号查询，近1个月在银行机构申请次数',
        'als_m1_id_bank_orgnum' => '按身份证号查询，近1个月在银行机构申请机构数',
        'als_m1_id_nbank_selfnum' => '按身份证号查询，近1个月在本机构(本机构为非银)申请次数',
        'als_m1_id_nbank_allnum' => '按身份证号查询，近1个月在非银机构申请次数',
        'als_m1_id_nbank_p2p_allnum' => '按身份证号查询，近1个月在非银机构-p2p申请次数',
        'als_m1_id_nbank_mc_allnum' => '按身份证号查询，近1个月在非银机构-小贷申请次数',
        'als_m1_id_nbank_ca_allnum' => '按身份证号查询，近1个月在非银机构-现金类分期申请次数',
        'als_m1_id_nbank_cf_allnum' => '按身份证号查询，近1个月在非银机构-消费类分期申请次数',
        'als_m1_id_nbank_com_allnum' => '按身份证号查询，近1个月在非银机构-代偿类分期申请次数',
        'als_m1_id_nbank_oth_allnum' => '按身份证号查询，近1个月在非银机构-其他申请次数',
        'als_m1_id_nbank_orgnum' => '按身份证号查询，近1个月在非银机构申请机构数',
        'als_m1_id_nbank_p2p_orgnum' => '按身份证号查询，近1个月在非银机构-p2p申请机构数',
        'als_m1_id_nbank_mc_orgnum' => '按身份证号查询，近1个月在非银机构-小贷申请机构数',
        'als_m1_id_nbank_ca_orgnum' => '按身份证号查询，近1个月在非银机构-现金类分期申请机构数',
        'als_m1_id_nbank_cf_orgnum' => '按身份证号查询，近1个月在非银机构-消费类分期申请机构数',
        'als_m1_id_nbank_com_orgnum' => '按身份证号查询，近1个月在非银机构-代偿类分期申请机构数',
        'als_m1_id_nbank_oth_orgnum' => '按身份证号查询，近1个月在非银机构-其他申请机构数',
        'als_m1_cell_bank_selfnum' => '按手机号查询，近1个月在本机构(本机构为银行)的申请次数',
        'als_m1_cell_bank_allnum' => '按手机号查询，近1个月在银行机构申请次数',
        'als_m1_cell_bank_orgnum' => '按手机号查询，近1个月在银行机构申请机构数',
        'als_m1_cell_nbank_selfnum' => '按手机号查询，近1个月在本机构(本机构为非银)申请次数',
        'als_m1_cell_nbank_allnum' => '按手机号查询，近1个月在非银机构申请次数',
        'als_m1_cell_nbank_p2p_allnum' => '按手机号查询，近1个月在非银机构-p2p申请次数',
        'als_m1_cell_nbank_mc_allnum' => '按手机号查询，近1个月在非银机构-小贷申请次数',
        'als_m1_cell_nbank_ca_allnum' => '按手机号查询，近1个月在非银机构-现金类分期申请次数',
        'als_m1_cell_nbank_cf_allnum' => '按手机号查询，近1个月在非银机构-消费类分期申请次数',
        'als_m1_cell_nbank_com_allnum' => '按手机号查询，近1个月在非银机构-代偿类分期申请次数',
        'als_m1_cell_nbank_oth_allnum' => '按手机号查询，近1个月在非银机构-其他申请次数',
        'als_m1_cell_nbank_orgnum' => '按手机号查询，近1个月在非银机构申请机构数',
        'als_m1_cell_nbank_p2p_orgnum' => '按手机号查询，近1个月在非银机构-p2p申请机构数',
        'als_m1_cell_nbank_mc_orgnum' => '按手机号查询，近1个月在非银机构-小贷申请机构数',
        'als_m1_cell_nbank_ca_orgnum' => '按手机号查询，近1个月在非银机构-现金类分期申请机构数',
        'als_m1_cell_nbank_cf_orgnum' => '按手机号查询，近1个月在非银机构-消费类分期申请机构数',
        'als_m1_cell_nbank_com_orgnum' => '按手机号查询，近1个月在非银机构-代偿类分期申请机构数',
        'als_m1_cell_nbank_oth_orgnum' => '按手机号查询，近1个月在非银机构-其他申请机构数',
        'als_m3_id_bank_selfnum' => '按身份证号查询，近3个月在本机构(本机构为银行)的申请次数',
        'als_m3_id_bank_allnum' => '按身份证号查询，近3个月在银行机构申请次数',
        'als_m3_id_bank_orgnum' => '按身份证号查询，近3个月在银行机构申请机构数',
        'als_m3_id_nbank_selfnum' => '按身份证号查询，近3个月在本机构(本机构为非银)申请次数',
        'als_m3_id_nbank_allnum' => '按身份证号查询，近3个月在非银机构申请次数',
        'als_m3_id_nbank_p2p_allnum' => '按身份证号查询，近3个月在非银机构-p2p申请次数',
        'als_m3_id_nbank_mc_allnum' => '按身份证号查询，近3个月在非银机构-小贷申请次数',
        'als_m3_id_nbank_ca_allnum' => '按身份证号查询，近3个月在非银机构-现金类分期申请次数',
        'als_m3_id_nbank_cf_allnum' => '按身份证号查询，近3个月在非银机构-消费类分期申请次数',
        'als_m3_id_nbank_com_allnum' => '按身份证号查询，近3个月在非银机构-代偿类分期申请次数',
        'als_m3_id_nbank_oth_allnum' => '按身份证号查询，近3个月在非银机构-其他申请次数',
        'als_m3_id_nbank_orgnum' => '按身份证号查询，近3个月在非银机构申请机构数',
        'als_m3_id_nbank_p2p_orgnum' => '按身份证号查询，近3个月在非银机构-p2p申请机构数',
        'als_m3_id_nbank_mc_orgnum' => '按身份证号查询，近3个月在非银机构-小贷申请机构数',
        'als_m3_id_nbank_ca_orgnum' => '按身份证号查询，近3个月在非银机构-现金类分期申请机构数',
        'als_m3_id_nbank_cf_orgnum' => '按身份证号查询，近3个月在非银机构-消费类分期申请机构数',
        'als_m3_id_nbank_com_orgnum' => '按身份证号查询，近3个月在非银机构-代偿类分期申请机构数',
        'als_m3_id_nbank_oth_orgnum' => '按身份证号查询，近3个月在非银机构-其他申请机构数',
        'als_m3_cell_bank_selfnum' => '按手机号查询，近3个月在本机构(本机构为银行)的申请次数',
        'als_m3_cell_bank_allnum' => '按手机号查询，近3个月在银行机构申请次数',
        'als_m3_cell_bank_orgnum' => '按手机号查询，近3个月在银行机构申请机构数',
        'als_m3_cell_nbank_selfnum' => '按手机号查询，近3个月在本机构(本机构为非银)申请次数',
        'als_m3_cell_nbank_allnum' => '按手机号查询，近3个月在非银机构申请次数',
        'als_m3_cell_nbank_p2p_allnum' => '按手机号查询，近3个月在非银机构-p2p申请次数',
        'als_m3_cell_nbank_mc_allnum' => '按手机号查询，近3个月在非银机构-小贷申请次数',
        'als_m3_cell_nbank_ca_allnum' => '按手机号查询，近3个月在非银机构-现金类分期申请次数',
        'als_m3_cell_nbank_cf_allnum' => '按手机号查询，近3个月在非银机构-消费类分期申请次数',
        'als_m3_cell_nbank_com_allnum' => '按手机号查询，近3个月在非银机构-代偿类分期申请次数',
        'als_m3_cell_nbank_oth_allnum' => '按手机号查询，近3个月在非银机构-其他申请次数',
        'als_m3_cell_nbank_orgnum' => '按手机号查询，近3个月在非银机构申请机构数',
        'als_m3_cell_nbank_p2p_orgnum' => '按手机号查询，近3个月在非银机构-p2p申请机构数',
        'als_m3_cell_nbank_mc_orgnum' => '按手机号查询，近3个月在非银机构-小贷申请机构数',
        'als_m3_cell_nbank_ca_orgnum' => '按手机号查询，近3个月在非银机构-现金类分期申请机构数',
        'als_m3_cell_nbank_cf_orgnum' => '按手机号查询，近3个月在非银机构-消费类分期申请机构数',
        'als_m3_cell_nbank_com_orgnum' => '按手机号查询，近3个月在非银机构-代偿类分期申请机构数',
        'als_m3_cell_nbank_oth_orgnum' => '按手机号查询，近3个月在非银机构-其他申请机构数',
        'als_m6_id_tot_mons' => '按身份证号查询，近6个月有申请记录月份数',
        'als_m6_id_avg_monnum' => '按身份证号查询，近6个月平均每月申请次数(有申请月份平均)',
        'als_m6_id_max_monnum' => '按身份证号查询，近6个月最大月申请次数',
        'als_m6_id_min_monnum' => '按身份证号查询，近6个月最小月申请次数',
        'als_m6_id_max_inteday' => '按身份证号查询，近6个月申请最大间隔天数',
        'als_m6_id_min_inteday' => '按身份证号查询，近6个月申请最小间隔天数',
        'als_m6_cell_tot_mons' => '按手机号查询，近6个月有申请记录月份数',
        'als_m6_cell_avg_monnum' => '按手机号查询，近6个月平均每月申请次数(有申请月份平均)',
        'als_m6_cell_max_monnum' => '按手机号查询，近6个月最大月申请次数',
        'als_m6_cell_min_monnum' => '按手机号查询，近6个月最小月申请次数',
        'als_m6_cell_max_inteday' => '按手机号查询，近6个月申请最大间隔天数',
        'als_m6_cell_min_inteday' => '按手机号查询，近6个月申请最小间隔天数',
        'als_fst_id_bank_inteday' => '按身份证号查询，距最早在银行机构申请的间隔天数',
        'als_fst_id_nbank_inteday' => '按身份证号查询，距最早在非银行机构申请的间隔天数',
        'als_fst_cell_bank_inteday' => '按手机号查询，距最早在银行机构申请的间隔天数',
        'als_fst_cell_nbank_inteday' => '按手机号查询，距最早在非银机构申请的间隔天数',
        'als_lst_id_bank_inteday' => '按身份证号查询，距最近在银行机构申请的间隔天数',
        'als_lst_id_bank_consnum' => '按身份证号查询，最近开始在银行机构连续申请的次数',
        'als_lst_id_bank_csinteday ' => '按身份证号查询，最近开始在银行机构连续申请的持续天数',
        'als_lst_id_nbank_inteday' => '按身份证号查询，距最近在非银行机构申请的间隔天数',
        'als_lst_id_nbank_consnum' => '按身份证号查询，最近开始在非银行机构连续申请的次数',
        'als_lst_id_nbank_csinteday' => '按身份证号查询，最近开始在非银机构连续申请的持续天数',
        'als_lst_cell_bank_inteday' => '按手机号查询，距最近在银行机构申请的间隔天数',
        'als_lst_cell_bank_consnum' => '按手机号查询，最近开始在银行机构连续申请的次数',
        'als_lst_cell_bank_csinteday' => '按手机号查询，最近开始在银行机构连续申请的持续天数',
        'als_lst_cell_nbank_inteday' => '按手机号查询，距最近在非银机构申请的间隔天数',
        'als_lst_cell_nbank_consnum' => '按手机号查询，最近开始在非银机构连续申请的次数',
        'als_lst_cell_nbank_csinteday' => '按手机号查询，最近开始在非银机构连续申请的持续天数'
    ];

    public $RegisterEquipment = [
        're_web_is_httpproxy' => '网页版是否使用http代理',
        're_web_is_simulator' => '网页版是否使用模拟器',
        're_web_is_exist' => '网页版是否获取到设备标识',
        're_web_is_speciallist' => '网页版是否命中异常名单',
        're_web_is_nightopr' => '网页版是否在夜间（1点至5点）操作',
        're_web_is_city_ip_cell' => '网页版手机与真实IP是否在同一个城市',
        're_web_period' => '网页版设备本次注册距前一次注册的时间间隔',
        're_web_h24_ip_num' => '网页版24小时内（含该小时）设备使用的IP数量',
        're_web_h24_uid_num' => '网页版24小时内（含该小时）设备使用的平台账号数量',
        're_web_d7_ip_num' => '网页版7天内（含当天）设备使用的IP数量',
        're_web_d7_uid_num' => '网页版7天内（含当天）设备使用的平台账号数量',
        're_andr_is_httpproxy' => '安卓版是否使用http代理',
        're_andr_is_simulator' => '安卓版是否使用模拟器',
        're_andr_is_exist' => '安卓版是否获取到设备标识',
        're_andr_is_speciallist' => '安卓版是否命中异常名单',
        're_andr_is_nightopr' => '安卓版是否在夜间（1点至5点）操作',
        're_andr_is_city_ip_cell' => '安卓版手机与真实IP是否在同一个城市',
        're_andr_period' => '安卓版设备本次注册距前一次注册的时间间隔',
        're_andr_h24_ip_num' => '安卓版24小时内（含该小时）设备使用的IP数量',
        're_andr_h24_uid_num' => '安卓版24小时内（含该小时）设备使用的平台账号数量',
        're_andr_d7_ip_num' => '安卓版7天内（含当天）设备使用的IP数量',
        're_andr_d7_uid_num' => '安卓版7天内（含当天）设备使用的平台账号数量',
        're_ios_is_httpproxy' => 'IOS版是否使用http代理',
        're_ios_is_simulator' => 'IOS版是否使用模拟器',
        're_ios_is_exist' => 'IOS版是否获取到设备标识',
        're_ios_is_speciallist' => 'IOS版是否命中异常名单',
        're_ios_is_nightopr' => 'IOS版是否在夜间（1点至5点）操作',
        're_ios_is_city_ip_cell' => 'IOS版手机与真实IP是否在同一个城市',
        're_ios_period' => 'IOS版设备本次注册距前一次注册的时间间隔',
        're_ios_h24_ip_num' => 'IOS版24小时内（含该小时）设备使用的IP数量',
        're_ios_h24_uid_num' => 'IOS版24小时内（含该小时）设备使用的平台账号数量',
        're_ios_d7_ip_num' => 'IOS版7天内（含当天）设备使用的IP数量',
        're_ios_d7_uid_num' => 'IOS版7天内（含当天）设备使用的平台账号数量',
    ];

    public $signequipment = [
        'se_web_is_httpproxy' => '网页版是否使用http代理',
        'se_web_is_simulator' => '网页版是否使用模拟器',
        'se_web_is_exist' => '网页版是否获取到设备标识',
        'se_web_is_nightopr' => '网页版是否在夜间（1点至5点）操作',
        'se_web_per' => '网页版设备登录与上次登录的时间间隔',
        'se_web_uid_per' => '网页版平台账号登录与上次登录时间间隔',
        'se_web_h1_dist' => '网页版1小时内（含本分钟）设备两次登录的距离间隔',
        'se_web_h1_uid_dist' => '网页版1小时内（含本分钟）平台账号两次登录的距离间隔',
        'se_web_h24_signnum' => '网页版24小时内（含该小时）设备登录次数',
        'se_web_h24_uid_signnum' => '网页版24小时内（含该小时）平台账号登录次数',
        'se_web_h24_ip_num' => '网页版24小时内（含该小时）设备使用的IP数量',
        'se_web_h24_uid_ip_num' => '网页版24小时内（含该小时）平台账号使用的IP数量',
        'se_web_h24_uid_num' => '网页版24小时内（含该小时）设备使用的平台账号数量',
        'se_web_h24_uid_gid_num' => '网页版24小时内（含该小时）平台账号使用的设备数量',
        'se_web_d7_ip_num' => '网页版7天内（含当天）设备使用的IP数量',
        'se_web_d7_uid_ip_num' => '网页版7天内（含当天）平台账号使用的IP数量',
        'se_web_d7_uid_num' => '网页版7天内（含当天）设备使用的平台账号数量',
        'se_web_d7_uid_gid_num' => '网页版7天内（含当天）平台账号使用的设备数量',
        'se_andr_is_httpproxy' => '安卓版是否使用http代理',
        'se_andr_is_simulator' => '安卓版是否使用模拟器',
        'se_andr_is_exist' => '安卓版是否获取到设备标识',
        'se_andr_is_nightopr' => '安卓版是否在夜间（1点至5点）操作',
        'se_andr_per' => '安卓版设备两次登录的时间间隔',
        'se_andr_uid_per' => '安卓版平台账号两次登录的时间间隔',
        'se_andr_h1_dist' => '安卓版1小时内（含本分钟）设备两次登录的距离间隔',
        'se_andr_h1_uid_dist' => '安卓版1小时内（含本分钟）平台账号两次登录的距离间隔',
        'se_andr_h24_signnum' => '安卓版24小时内（含该小时）设备登录次数',
        'se_andr_h24_uid_signnum' => '安卓版24小时内（含该小时）平台账号登录次数',
        'se_andr_h24_ip_num' => '安卓版24小时内（含该小时）设备使用的IP数量',
        'se_andr_h24_uid_ip_num' => '安卓版24小时内（含该小时）台账号使用的IP数量',
        'se_andr_h24_uid_num' => '安卓版24小时内（含该小时）设备使用的平台账号数量',
        'se_andr_h24_uid_gid_num' => '安卓版24小时内（含该小时）平台账号使用的设备数量',
        'se_andr_d7_ip_num' => '安卓版7天内（含当天）设备使用的IP数量',
        'se_andr_d7_uid_ip_num' => '安卓版7天内（含当天）平台账号使用的IP数量',
        'se_andr_d7_uid_num' => '安卓版7天内（含当天）设备使用的平台账号数量',
        'se_andr_d7_uid_gid_num' => '安卓版7天内（含当天）平台账号使用的设备数量',
        'se_ios_is_httpproxy' => 'IOS版是否使用http代理',
        'se_ios_is_simulator' => 'IOS版是否使用模拟器',
        'se_ios_is_exist' => 'IOS版是否获取到设备标识',
        'se_ios_is_nightopr' => 'IOS版是否在夜间（1点至5点）操作',
        'se_ios_per' => 'IOS版设备两次登录的时间间隔',
        'se_ios_uid_per' => 'IOS版平台账号两次登录的时间间隔',
        'se_ios_h1_dist' => 'IOS版1小时内（含本分钟）两登录的距离间隔',
        'se_ios_h1_uid_dist' => 'IOS版1小时内（含本分钟）平台账号两次登录的距离间隔',
        'se_ios_h24_signnum' => 'IOS版24小时内（含该小时）设备登录次数',
        'se_ios_h24_uid_signnum' => 'IOS版24小时内（含该小时）平台账号登录次数',
        'se_ios_h24_ip_num' => 'IOS版24小时内（含该小时）设备使用的IP数量',
        'se_ios_h24_uid_ip_num' => 'IOS版24小时内（含该小时）平台账号使用的IP数量',
        'se_ios_h24_uid_num' => 'IOS版24小时内（含该小时）设备使用的平台账号数量',
        'se_ios_h24_uid_gid_num' => 'IOS版24小时内（含该小时）平台账号使用的设备数量',
        'se_ios_d7_ip_num' => 'IOS版7天内（含当天）设备使用的IP数量',
        'se_ios_d7_uid_ip_num' => 'IOS版7天内（含当天）平台账号使用的IP数量',
        'se_ios_d7_uid_num' => 'IOS版7天内（含当天）设备使用的平台账号数量',
        'se_ios_d7_uid_gid_num' => 'IOS版7天内（含当天）平台账号使用的设备数量',
    ];

    public $loanequipment = [
        'le_web_is_httpproxy' => '网页版是否使用http代理',
        'le_web_is_simulator' => '网页版是否使用模拟器',
        'le_web_is_exist' => '网页版是否获取到设备标识',
        'le_web_is_nightopr' => '网页版是否在夜间（1点至5点）操作',
        'le_web_is_city_ip_cell' => '网页版手机与真实IP是否在同一个城市',
        'le_web_loan_per' => '网页版设备借款与上次借款的时间间隔',
        'le_web_sign_per' => '网页版设备借款与上次借款的时间间隔',
        'le_web_uid_sign_per' => '网页版平台账号借款与上次登录时间间隔',
        'le_web_h24_loannum' => '网页版过去24小时内（含该小时）设备借款次数',
        'le_web_h24_ip_num' => '网页版过去24小时内（含该小时）设备使用的IP数量',
        'le_web_h24_uid_ip_num' => '网页版过去24小时内（含该小时）平台账号使用的IP数量',
        'le_web_h24_uid_num' => '网页版过去24小时内（含该小时）设备使用的平台账号数量',
        'le_web_h24_uid_gid_num' => '网页版过去24小时内（含该小时）平台账号使用的设备数量',
        'le_web_d7_loannum' => '网页版过去7天内（含当天）设备借款次数',
        'le_web_d7_ip_num' => '网页版过去7天内（含当天）设备使用的IP数量',
        'le_web_d7_uid_ip_num' => '网页版过去7天内（含当天）平台账号使用的IP数量',
        'le_web_d7_uid_num' => '网页版过去7天内（含当天）设备使用的平台账号数量',
        'le_web_d7_uid_gid_num' => '网页版过去7天内（含当天）平台账号使用的设备数量',
        'le_web_city_num' => '网页版上网地市总数（地级市）',
        'le_web_city_top1' => '网页版上网地市top1',
        'le_web_city_top1_td' => '网页版上网地市top1累计上网天数',
        'le_web_city_top1_rate' => '网页版上网地市top1累计上网天数占比',
        'le_web_city_top2' => '网页版上网地市top2',
        'le_web_city_top2_td' => '网页版上网地市top2累计上网天数',
        'le_web_city_top2_rate' => '网页版上网地市top2累计上网天数占比',
        'le_web_city_top3' => '网页版上网地市top3',
        'le_web_city_top3_td' => '网页版上网地市top3累计上网天数',
        'le_web_city_top3_rate' => '网页版上网地市top3累计上网天数占比',
        'le_web_conscat_num' => '网页版电商上网类目总数',
        'le_web_conscat_top1' => '网页版电商上网类目top1',
        'le_web_conscat_top1_td' => '网页版电商上网类目top1累计上网天数',
        'le_web_conscat_top1_rate' => '网页版电商上网类目top1累计上网天数占比',
        'le_web_conscat_top2' => '网页版电商上网类目top2',
        'le_web_conscat_top2_td' => '网页版电商上网类目top2累计上网天数',
        'le_web_conscat_top2_rate' => '网页版电商上网类目top2累计上网天数占比',
        'le_web_conscat_top3' => '网页版电商上网类目top3',
        'le_web_conscat_top3_td' => '网页版电商上网类目top3累计上网天数',
        'le_web_conscat_top3_rate' => '网页版电商上网类目top3累计上网天数占比',
        'le_web_mediacat_num' => '网页版媒体上网类目总数',
        'le_web_mediacat_top1' => '网页版媒体上网类目top1',
        'le_web_mediacat_top1_td' => '网页版媒体上网类目top1累计上网天数',
        'le_web_mediacat_top1_rate' => '网页版媒体上网类目top1累计上网天数占比',
        'le_web_mediacat_top2' => '网页版媒体上网类目top2',
        'le_web_mediacat_top2_td' => '网页版媒体上网类目top2累计上网天数',
        'le_web_mediacat_top2_rate' => '网页版媒体上网类目top2累计上网天数占比',
        'le_web_mediacat_top3' => '网页版媒体上网类目top3',
        'le_web_mediacat_top3_td' => '网页版媒体上网类目top3累计上网天数',
        'le_web_mediacat_top3_rate' => '网页版媒体上网类目top3累计上网天数占比',
        'le_web_lt_date' => '网页版最近上网时间',
        'le_web_lt_city' => '网页版最近上网时间所在地市',
        'le_web_lt_cat' => '网页版最近上网时间上网类目',
        'le_web_source' => '网页版上网信息数据来源',
        'le_andr_is_httpproxy' => '安卓版是否使用http代理',
        'le_andr_is_simulator' => '安卓版是否使用模拟器',
        'le_andr_is_exist' => '安卓版是否获取到设备标识',
        'le_andr_is_nightopr' => '安卓版是否在夜间（1点至5点）操作',
        'le_andr_is_city_ip_cell' => '安卓版手机与真实IP是否在同一个城市',
        'le_andr_loan_per' => '安卓版设备借款与上次借款的时间间隔',
        'le_andr_sign_per' => '安卓版设备借款与前次登录的时间间隔',
        'le_andr_uid_sign_per' => '安卓版平台账号本次借款距前一次登录的时间间隔',
        'le_andr_h24_loannum' => '安卓版24小时内（含该小时）设备借款次数',
        'le_andr_h24_ip_num' => '安卓版24小时内（含该小时）设备使用的IP数量',
        'le_andr_h24_uid_ip_num' => '安卓版24小时内（含该小时）平台账号使用的IP数量',
        'le_andr_h24_uid_num' => '安卓版24小时内（含该小时）设备使用的平台账号数量',
        'le_andr_h24_uid_gid_num' => '安卓版24小时内（含该小时）平台账号使用的设备数量',
        'le_andr_d7_loannum' => '安卓版7天内（含当天）设备借款次数',
        'le_andr_d7_ip_num' => '安卓版7天内（含当天）设备使用的IP数量',
        'le_andr_d7_uid_ip_num' => '安卓版7天内（含当天）平台账号使用的IP数量',
        'le_andr_d7_uid_num' => '安卓版7天内（含当天）设备使用的平台账号数量',
        'le_andr_d7_uid_gid_num' => '安卓版7天内（含当天）平台账号使用的设备数量',
        'le_ios_is_httpproxy' => 'IOS版是否使用http代理',
        'le_ios_is_simulator' => 'IOS版是否使用模拟器',
        'le_ios_is_exist' => 'IOS版是否获取到设备标识',
        'le_ios_is_nightopr' => 'IOS版是否在夜间（1点至5点）操作',
        'le_ios_is_city_ip_cell' => 'IOS版手机与真实IP是否在同一个城市',
        'le_ios_loan_per' => 'IOS版设备借款与上次借款的时间间隔',
        'le_ios_sign_per' => 'IOS版设备借款与前次登录的时间间隔',
        'le_ios_uid_sign_per' => 'IOS版平台账号本次借款距前一次登录的时间间隔',
        'le_ios_h24_loannum' => 'IOS版24小时内（含该小时）设备借款次数',
        'le_ios_h24_ip_num' => 'IOS版24小时内（含该小时）设备使用的IP数量',
        'le_ios_h24_uid_ip_num' => 'IOS版24小时内（含该小时）平台账号使用的IP数量',
        'le_ios_h24_uid_num' => 'IOS版24小时内（含该小时）设备使用的平台账号数量',
        'le_ios_h24_uid_gid_num' => 'IOS版24小时内（含该小时）平台账号使用的设备数量',
        'le_ios_d7_loannum' => 'IOS版7天内（含当天）设备借款次数',
        'le_ios_d7_ip_num' => 'IOS版7天内（含当天）设备使用的IP数量',
        'le_ios_d7_uid_ip_num' => 'IOS版7天内（含当天）平台账号使用的IP数量',
        'le_ios_d7_uid_num' => 'IOS版7天内（含当天）设备使用的平台账号数量',
        'le_ios_d7_uid_gid_num' => 'IOS版7天内（含当天）平台账号使用的设备数量',
    ];
    public $equipmentcheck = [
        'eqc_id_num' => '匹配设备关联身份证号个数',
        'eqc_cell_num' => '匹配设备关联手机号个数',
        'eqc_ac_firsttime' => '匹配设备激活sim卡最早年份',
        'eqc_mail_num' => '匹配设备关联邮箱个数',
        'eqc_id_gid_num' => '匹配设备关联身份证号对应关联的设备数量',
        'eqc_cell_gid_num' => '匹配设备关联手机号对应关联的设备数量',
        'eqc_auth_key_relation' => 'gid与关健值(id、cell、mail)的一致性校验',
        'eqc_id_num_fin' => '金融行业中，匹配设备关联身份证号个数',
        'eqc_cell_num_fin' => '金融行业中，匹配设备关联手机号个数',
        'eqc_mail_num_fin' => '金融行业中，匹配设备关联邮箱个数',
        'eqc_id_gid_num_fin' => '金融行业中，匹配设备关联身份证号对应关联的设备数量',
        'eqc_cell_gid_num_fin' => '金融行业中，匹配设备关联手机号对应关联的设备数量',
        'eqc_auth_key_relation_fin' => '金融行业gid与关健值(id、cell、mail)的一致性校验',
    ];

    public $flag = [
        0 => '未匹配上无输出',
        1 => '输出成功',
        98 => '用户输入信息不足',
        99 => '系统异常'
    ];

    public function BrList($data,$type){
        $res = '数据暂无';
        $arr = json_decode($data,true);
        if(!$arr)
            return $res;
        switch($type){
            case CreditBr::SPECIAL_LIST:
                $res = $this->SpecialListC($arr,$type);
                break;
            case CreditBr::APPLY_LOAN_STR:
                $res = $this->ApplyLoanStr($arr,$type);
                break;
            case CreditBr::REGISTER_EQUIPMENT:
                $res = $this->RegisterEquipment($arr,$type);
                break;
            case CreditBr::SIGN_EQUIPMENT:
                $res = $this->SignEquipment($arr,$type);
                break;
            case CreditBr::LOAN_EQUIPMENT:
                $res = $this->LoanEquipment($arr,$type);
                break;
            case CreditBr::EQUIPMENT_CHECK:
                $res = $this->EquipmentCheck($arr,$type);
                break;
        }
        return $res;
    }
    /*
     *  特殊名单核查
     */
    private function SpecialListC($arr){
        $res['查询结果'] = $this->flag[$arr['flag_specialList_c']];
        if($arr['flag_specialList_c'] == 1){
            foreach ($arr as $key => $val){
                foreach ($this->specialList_c as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }

    private function ApplyLoanStr($arr){
        $res['查询结果'] = $this->flag[$arr['flag_applyloanstr']];
        if($arr['flag_applyloanstr'] == 1){
            foreach ($arr as $key => $val){
                foreach ($this->applyloanstr as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }

    private function RegisterEquipment($arr){
        $res['查询结果'] = $this->flag[$arr['flag_registerequipment']];
        if($arr['flag_registerequipment'] == 1){
            foreach ($arr as $key => $val){
                foreach ($this->RegisterEquipment as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }

    private function SignEquipment($arr){
        $res['查询结果'] = $this->flag[$arr['flag_signequipment']];
        if($arr['flag_signequipment'] == 1){
            foreach ($arr as $key => $val){
                foreach ($this->signequipment as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }

    private function LoanEquipment($arr){
        $res['查询结果'] = $this->flag[$arr['flag_loanequipment']];
        if($arr['flag_loanequipment'] == 1){
            foreach ($arr as $key => $val){
                foreach ($this->loanequipment as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }
    private function EquipmentCheck($arr){
        $res['查询结果'] = $this->flag[$arr['flag_equipmentcheck']];
        if($arr['flag_equipmentcheck']){
            foreach ($arr as $key => $val){
                foreach ($this->equipmentcheck as $key1 => $val1){
                    if($key == $key1){
                        $res[$val1] = $val;
                    }
                }
            }
        }
        return $res;
    }
}



