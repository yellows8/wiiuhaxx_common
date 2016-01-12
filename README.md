This is a common codebase for generating ROP-chains/etc for *seperate* Wii U PowerPC-userland exploits. This uses addresses auto-located from coreinit, with .php for each sysver that was pre-generated. This is basically a Wii U version of this: https://github.com/yellows8/3ds_browserhax_common

Currently only binary ROP-chains are supported, hence no support for using this easily with WebKit exploits. The core1-switch ROP here doesn't work correctly currently, hence this codebase isn't usable as-is unless the current core is already core1(which isn't the case for WebKit exploits it seems). In other words, this codebase is currently only usable with non-WebKit exploits. Hence the use of php, this is intended for running under browser-based titles, but could be used with others as well if the ROP-chain(s) are usable with them.

You must specify the "sysver={val}" URL parameter for pages using this codebase, for selecting your Wii U system-version:
* "532": 5.3.2
* "540": 5.4.0
* "550": 5.5.0 / 5.5.1

# Usage

This codebase uses config/etc names from the 3ds repo mentioned above.

If the exploit .php has to select a sysver without using the value selected with via the URL param, that could be done with the following for example(prior to using the require_once() for the common .php): "$sysver = 550;" DO NOT set $sysver to any data directly specified by the user.

The $ropchainselect field determines which ROP-chain to use. Only one ROP-chain is implemented currently. When this param isn't specified, the codebase will select the default ROP-chain(val1).

The default ROP-chain does the following(only usable with titles which have codegen access):
* 1) Runs the core1-switch ROP(see above).
* 2) Loads the codebin payload into the codegen/JIT area(with the required OSSwitchSecCodeGenMode() calls before/after), and does dcache/icache flushing/invalidation.
* 3) Pops addresses into registers which the codebin could then use, see source.
* 4) Jumps to the codebin payload.
* 5) See source.

The memory address of the codebin payload is required, see below. If the payload isn't guaranteed to always be at the exact same address all the time, storing a PowerPC NOP-sled right before the payload in memory is highly recommended(big-endian word value 0x60000000). The output of wiiuhaxx_generatepayload() is: wiiuhaxx_loader followed by the actual payload, with 4-byte alignment for the total size(see below).

Also note that this codebase *itself* does not need the address where the initial ROP-chain is located at all.

Example that the exploit .php can use:

```
<?php

...

require_once("wiiu_browserhax_common.php");

...

$generatebinrop = 1;
$payload_srcaddr = <address of payload codebin>;
$ROPHEAP = <some valid address the ROP can use for storing tmp data>;//Such as the following: $payload_srcaddr-0x1000.
generate_ropchain();

...

$payload = wiiuhaxx_generatepayload();//Binary codebin, include this in the output exploit so that it lands in memory usable by the ROP.
if($payload === FALSE)
{
	header("HTTP/1.1 500 Internal Server Error");
	die("The payload binary doesn't exist / is invalid.\n");
}

...

else if($i<{targetoffset})
{
	$writeval = $ROP_POPJUMPLR_STACK12;//ROP NOP-sled.
}
else if($i=={targetoffset})
{
	$con.= pack("N*", $ROP_POPJUMPLR_STACK12);
	$con.= pack("N*", 0x48484848);//If LR ever gets loaded from here there's no known way to recover from that automatically, this code would need manually adjusted if that ever happens.
	$i+= 0x8;
	$con.= $ROPCHAIN;
	$i+= strlen($ROPCHAIN)-4;

	//Verify that the $ROPCHAIN isn't too large somewhere in here.

	continue;
}

...
?>
```

A config file located at "wiiuhaxx_common_cfg.php" is also required.
* $wiiuhaxxcfg_payloadfilepath is the filepath for the actual codebin payload to run, this will be loaded to codegen+0.
* $wiiuhaxxcfg_loaderfilepath is the filepath for wiiuhaxx_loader.bin. This loads the above payload, since due to the NOP-sled the initial code which runs(wiiuhaxx_loader) won't (always) land at codegen+0. This can be built by running the following: "make OUTPATH={directorypath/filepath to copy the .bin to}".

For example:

```
<?php

$wiiuhaxxcfg_payloadfilepath = "<filepath for actual payload, such as wiiuhax_payload.bin, or for example: {projectdir}/bin/code550.bin>";
$wiiuhaxxcfg_loaderfilepath = "<filepath for wiiuhaxx_loader.bin>";

?>
```

