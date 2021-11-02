---
title: Executing "lsmem" may cause a kernel panic in is_mem_section_removable() or hang the system
description: ''
layout: doc
categories: [problem]
tags: [persistent memory, pmem, lsmem, hang, crash]
author: Steve Scargall
docid: 100000027
creation_date: 2020-04-09
modified_date: 
---
# Applies To

- Linux

- lsmem utility

- Intel Optane Persistent Memory

  

# Issue

When executing the 'lsmem' utility with persistent memory installed, Debian based systems, such as Ubuntu, may hang with no error reported and require a power-cycle to recover. Fedora, CentOS, and RHEL, for example, may crash and produce an error similar to the following:

```
// Fedora Non-Debug Kernel:
$ lsmem
Killed

// Fedora Debug Kernel:
# lsmem
Segmentation fault

```

When the kernel panics, dmesg will report errors similar to the following examples:

**Example 1: Fedora Non-Debug Kernel** 

```
[  160.185125] BUG: unable to handle page fault for address: 0000000000002df8
[  160.185161] #PF: supervisor read access in kernel mode
[  160.185182] #PF: error_code(0x0000) - not-present page
[  160.185203] PGD 0 P4D 0
[  160.185216] Oops: 0000 [#1] SMP NOPTI
[  160.185232] CPU: 4 PID: 10012 Comm: lsmem Not tainted 5.5.10-200.fc31.x86_64 #1
[  160.185260] Hardware name: Intel Corporation S2600WFD/S2600WFD, BIOS SE5C620.86B.02.01.0010.010620200716 01/06/2020
[  160.185302] RIP: 0010:is_mem_section_removable+0x46/0x150
[  160.185324] Code: 04 30 48 89 fb 48 89 c2 48 89 c1 48 c1 ea 33 48 c1 e9 36 83 e2 07 48 8d 3c 52 48 8d 14 ba 48 c1 e2 07 48 03 14 cd a0 f7 82 b5 <4c> 8b 62 78 4c 03 62 68 4a 8d 14 03 49 39 d4 4c 0f 47 e2 4c 39 e3
[  160.185390] RSP: 0018:ffffbc0a8ea5fdf8 EFLAGS: 00010202
[  160.185410] RAX: ffffffffffffffff RBX: 0000000003060000 RCX: 00000000000003ff
[  160.185436] RDX: 0000000000002d80 RSI: ffffdd5c40000000 RDI: 0000000000000015
[  160.185462] RBP: ffff947317e6d000 R08: 0000000000008000 R09: ffff961df87f5030
[  160.185488] R10: ffff9474315bf640 R11: 0000000000000000 R12: ffff961df87f5030
[  160.185514] R13: 0000000000000000 R14: ffff947410fb72a8 R15: ffff947410fb72c0
[  160.185540] FS:  00007f0c82343740(0000) GS:ffff947440700000(0000) knlGS:0000000000000000
[  160.185569] CS:  0010 DS: 0000 ES: 0000 CR0: 0000000080050033
[  160.185591] CR2: 0000000000002df8 CR3: 0000002f690d4005 CR4: 00000000007606e0
[  160.185617] DR0: 0000000000000000 DR1: 0000000000000000 DR2: 0000000000000000
[  160.185644] DR3: 0000000000000000 DR6: 00000000fffe0ff0 DR7: 0000000000000400
[  160.185669] PKRU: 55555554
[  160.185681] Call Trace:
[  160.185699]  removable_show+0x6e/0xa0
[  160.185717]  dev_attr_show+0x19/0x40
[  160.185735]  sysfs_kf_seq_show+0x9b/0xf0
[  160.185751]  seq_read+0xcd/0x440
[  160.185767]  vfs_read+0x9d/0x150
[  160.185782]  ksys_read+0x5f/0xe0
[  160.185799]  do_syscall_64+0x5b/0x1c0
[  160.185819]  entry_SYSCALL_64_after_hwframe+0x44/0xa9
[  160.185840] RIP: 0033:0x7f0c82438412
[  160.185855] Code: c0 e9 c2 fe ff ff 50 48 8d 3d 52 0d 0a 00 e8 f5 f1 01 00 0f 1f 44 00 00 f3 0f 1e fa 64 8b 04 25 18 00 00 00 85 c0 75 10 0f 05 <48> 3d 00 f0 ff ff 77 56 c3 0f 1f 44 00 00 48 83 ec 28 48 89 54 24
[  160.185920] RSP: 002b:00007ffea9460d48 EFLAGS: 00000246 ORIG_RAX: 0000000000000000
[  160.185948] RAX: ffffffffffffffda RBX: 000055a261661b80 RCX: 00007f0c82438412
[  160.185975] RDX: 0000000000001000 RSI: 000055a261661ee0 RDI: 0000000000000004
[  160.186000] RBP: 00007f0c8250a300 R08: 0000000000000004 R09: 0000000000000070
[  160.186026] R10: 0000000000000000 R11: 0000000000000246 R12: 000055a261661b80
[  160.186052] R13: 00007f0c82509700 R14: 0000000000000d68 R15: 0000000000000d68
[  160.186081] Modules linked in: xt_CHECKSUM xt_MASQUERADE nf_nat_tftp nf_conntrack_tftp xt_CT tun bridge stp llc ip6t_REJECT nf_reject_ipv6 ip6t_rpfilter ipt_REJECT nf_reject_ipv4 xt_conntrack ebtable_nat ebtable_broute ip6table_nat ip6table_mangle ip6table_raw ip6table_security iptable_nat nf_nat rfkill iptable_mangle iptable_raw iptable_security nf_conntrack nf_defrag_ipv6 nf_defrag_ipv4 libcrc32c ip_set nfnetlink ebtable_filter ebtables ip6table_filter ip6_tables iptable_filter ib_isert iscsi_target_mod vfat fat ib_srpt target_core_mod ib_srp scsi_transport_srp ib_ipoib ib_umad intel_rapl_msr intel_rapl_common isst_if_common rpcrdma sunrpc skx_edac rdma_ucm ib_iser x86_pkg_temp_thermal rdma_cm intel_powerclamp iw_cm coretemp ib_cm libiscsi scsi_transport_iscsi kvm_intel kvm irqbypass crct10dif_pclmul crc32_pclmul iTCO_wdt ghash_clmulni_intel iTCO_vendor_support intel_cstate i40iw ipmi_ssif intel_uncore ib_uverbs ib_core intel_rapl_perf joydev ipmi_si lpc_ich i2c_i801 mei_me ioatdma mei
[  160.186119]  ipmi_devintf dax_pmem dca ipmi_msghandler dax_pmem_core ip_tables nd_pmem nd_btt ast i2c_algo_bit drm_vram_helper drm_ttm_helper nvme ttm drm_kms_helper nvme_core i40e crc32c_intel uas drm usb_storage nfit libnvdimm wmi
[  160.192867] CR2: 0000000000002df8
[  160.193516] ---[ end trace 21106b77367d68fb ]---
[  160.232695] RIP: 0010:is_mem_section_removable+0x46/0x150
[  160.233394] Code: 04 30 48 89 fb 48 89 c2 48 89 c1 48 c1 ea 33 48 c1 e9 36 83 e2 07 48 8d 3c 52 48 8d 14 ba 48 c1 e2 07 48 03 14 cd a0 f7 82 b5 <4c> 8b 62 78 4c 03 62 68 4a 8d 14 03 49 39 d4 4c 0f 47 e2 4c 39 e3
[  160.234564] RSP: 0018:ffffbc0a8ea5fdf8 EFLAGS: 00010202
[  160.235092] RAX: ffffffffffffffff RBX: 0000000003060000 RCX: 00000000000003ff
[  160.235621] RDX: 0000000000002d80 RSI: ffffdd5c40000000 RDI: 0000000000000015
[  160.236110] RBP: ffff947317e6d000 R08: 0000000000008000 R09: ffff961df87f5030
[  160.236628] R10: ffff9474315bf640 R11: 0000000000000000 R12: ffff961df87f5030
[  160.237139] R13: 0000000000000000 R14: ffff947410fb72a8 R15: ffff947410fb72c0
[  160.237682] FS:  00007f0c82343740(0000) GS:ffff947440700000(0000) knlGS:0000000000000000
[  160.238202] CS:  0010 DS: 0000 ES: 0000 CR0: 0000000080050033
[  160.238723] CR2: 0000000000002df8 CR3: 0000002f690d4005 CR4: 00000000007606e0
[  160.239216] DR0: 0000000000000000 DR1: 0000000000000000 DR2: 0000000000000000
[  160.239705] DR3: 0000000000000000 DR6: 00000000fffe0ff0 DR7: 0000000000000400
[  160.240187] PKRU: 55555554
```

**Example 2: Fedora Debug Kernel**

```
[  148.795546] page:ffffd2c7c1800000 is uninitialized and poisoned
[  148.795550] raw: ffffffffffffffff ffffffffffffffff ffffffffffffffff ffffffffffffffff
[  148.796002] raw: ffffffffffffffff ffffffffffffffff ffffffffffffffff ffffffffffffffff
[  148.796036] page dumped because: VM_BUG_ON_PAGE(PagePoisoned(p))
[  148.796074] ------------[ cut here ]------------
[  148.796098] kernel BUG at include/linux/mm.h:1087!
[  148.796126] invalid opcode: 0000 [#1] SMP NOPTI
[  148.796146] CPU: 63 PID: 5471 Comm: lsmem Not tainted 5.5.10-200.fc31.x86_64+debug #1
[  148.796173] Hardware name: Intel Corporation S2600WFD/S2600WFD, BIOS SE5C620.86B.02.01.0010.010620200716 01/06/2020
[  148.796212] RIP: 0010:is_mem_section_removable+0x1a4/0x1b0
[  148.796233] Code: 48 c7 c6 80 3e 39 a0 4c 89 cf e8 87 c9 f9 ff 0f 0b 5b b8 01 00 00 00 5d 41 5c c3 48 c7 c6 80 3e 39 a0 4c 89 cf e8 6c c9 f9 ff <0f> 0b 66 2e 0f 1f 84 00 00 00 00 00 0f 1f 44 00 00 4c 8d 8f 00 80
[  148.796290] RSP: 0018:ffffa1d10fd97de8 EFLAGS: 00010246
[  148.796310] RAX: 0000000000000034 RBX: 000000000000000c RCX: 0000000000000007
[  148.796334] RDX: 0000000000000000 RSI: ffff9500234847f8 RDI: ffff9500401dc5b0
[  148.796359] RBP: ffff94ff4f173000 R08: 00000022a4efbf26 R09: 0000000000000000
[  148.796383] R10: 0000000000000000 R11: 0000000000000000 R12: 00000000c1800000
[  148.796407] R13: 0000000000000000 R14: ffff95002c4dd728 R15: ffff95002c4dd740
[  148.796431] FS:  00007f3ab1551740(0000) GS:ffff950040000000(0000) knlGS:0000000000000000
[  148.796458] CS:  0010 DS: 0000 ES: 0000 CR0: 0000000080050033
[  148.796478] CR2: 00007f3ab16c0000 CR3: 0000002f6e690001 CR4: 00000000007606e0
[  148.796503] DR0: 0000000000000000 DR1: 0000000000000000 DR2: 0000000000000000
[  148.796526] DR3: 0000000000000000 DR6: 00000000fffe0ff0 DR7: 0000000000000400
[  148.796550] PKRU: 55555554
[  148.796561] Call Trace:
[  148.796591]  removable_show+0x6e/0xa0
[  148.796608]  dev_attr_show+0x19/0x40
[  148.796625]  sysfs_kf_seq_show+0xa9/0x100
[  148.796640]  seq_read+0xd5/0x450
[  148.796657]  vfs_read+0xc5/0x180
[  148.796672]  ksys_read+0x68/0xe0
[  148.796688]  do_syscall_64+0x5c/0xa0
[  148.796704]  entry_SYSCALL_64_after_hwframe+0x49/0xbe
[  148.796721] RIP: 0033:0x7f3ab1646412
[  148.796733] Code: c0 e9 c2 fe ff ff 50 48 8d 3d 52 0d 0a 00 e8 f5 f1 01 00 0f 1f 44 00 00 f3 0f 1e fa 64 8b 04 25 18 00 00 00 85 c0 75 10 0f 05 <48> 3d 00 f0 ff ff 77 56 c3 0f 1f 44 00 00 48 83 ec 28 48 89 54 24
[  148.796798] RSP: 002b:00007ffed86e6368 EFLAGS: 00000246 ORIG_RAX: 0000000000000000
[  148.796819] RAX: ffffffffffffffda RBX: 000055dfa1addb80 RCX: 00007f3ab1646412
[  148.796839] RDX: 0000000000001000 RSI: 000055dfa1addee0 RDI: 0000000000000004
[  148.796858] RBP: 00007f3ab1718300 R08: 0000000000000004 R09: 0000000000000070
[  148.796878] R10: 0000000000000000 R11: 0000000000000246 R12: 000055dfa1addb80
[  148.796898] R13: 00007f3ab1717700 R14: 0000000000000d68 R15: 0000000000000d68
[  148.797433] Modules linked in: xt_CHECKSUM xt_MASQUERADE nf_nat_tftp nf_conntrack_tftp xt_CT tun bridge stp llc ip6t_REJECT nf_reject_ipv6 ip6t_rpfilter ipt_REJECT nf_reject_ipv4 xt_conntrack ebtable_nat ebtable_broute ip6table_nat ip6table_mangle ip6table_raw ip6table_security iptable_nat nf_nat iptable_mangle iptable_raw iptable_security nf_conntrack nf_defrag_ipv6 nf_defrag_ipv4 rfkill libcrc32c ip_set nfnetlink ebtable_filter ebtables ip6table_filter ip6_tables iptable_filter ib_isert iscsi_target_mod ib_srpt target_core_mod ib_srp scsi_transport_srp ib_ipoib ib_umad vfat fat intel_rapl_msr intel_rapl_common rpcrdma sunrpc isst_if_common rdma_ucm ib_iser skx_edac x86_pkg_temp_thermal intel_powerclamp rdma_cm coretemp iw_cm ib_cm kvm_intel libiscsi scsi_transport_iscsi kvm iTCO_wdt iTCO_vendor_support irqbypass crct10dif_pclmul crc32_pclmul ghash_clmulni_intel intel_cstate ipmi_ssif i40iw ib_uverbs ipmi_si intel_uncore ib_core joydev mei_me ioatdma ipmi_devintf intel_rapl_perf i2c_i801
[  148.797470]  lpc_ich mei dca ipmi_msghandler dax_pmem dax_pmem_core ip_tables nd_pmem nd_btt ast i2c_algo_bit drm_vram_helper drm_ttm_helper ttm drm_kms_helper uas nvme crc32c_intel i40e nvme_core nfit usb_storage drm wmi libnvdimm
[  148.802835] ---[ end trace 5ed4b4ad8cf37162 ]---
[  148.860280] RIP: 0010:is_mem_section_removable+0x1a4/0x1b0
[  148.861016] Code: 48 c7 c6 80 3e 39 a0 4c 89 cf e8 87 c9 f9 ff 0f 0b 5b b8 01 00 00 00 5d 41 5c c3 48 c7 c6 80 3e 39 a0 4c 89 cf e8 6c c9 f9 ff <0f> 0b 66 2e 0f 1f 84 00 00 00 00 00 0f 1f 44 00 00 4c 8d 8f 00 80
[  148.862282] RSP: 0018:ffffa1d10fd97de8 EFLAGS: 00010246
[  148.862871] RAX: 0000000000000034 RBX: 000000000000000c RCX: 0000000000000007
[  148.863453] RDX: 0000000000000000 RSI: ffff9500234847f8 RDI: ffff9500401dc5b0
[  148.864028] RBP: ffff94ff4f173000 R08: 00000022a4efbf26 R09: 0000000000000000
[  148.864594] R10: 0000000000000000 R11: 0000000000000000 R12: 00000000c1800000
[  148.865160] R13: 0000000000000000 R14: ffff95002c4dd728 R15: ffff95002c4dd740
[  148.865721] FS:  00007f3ab1551740(0000) GS:ffff950040000000(0000) knlGS:0000000000000000
[  148.866296] CS:  0010 DS: 0000 ES: 0000 CR0: 0000000080050033
[  148.866870] CR2: 00007f3ab16c0000 CR3: 0000002f6e690001 CR4: 00000000007606e0
[  148.867442] DR0: 0000000000000000 DR1: 0000000000000000 DR2: 0000000000000000
[  148.868014] DR3: 0000000000000000 DR6: 00000000fffe0ff0 DR7: 0000000000000400
[  148.868574] PKRU: 55555554
```



# Cause

The root cause is a kernel bug. See [this discussion](https://patchwork.kernel.org/patch/11353875/) for more details.

# Solution

The issue is resolved by [this Kernel commit](https://git.kernel.org/pub/scm/linux/kernel/git/torvalds/linux.git/commit/?id=53cdc1cb29e87ce5a61de5bb393eb08925d14ede) in the following mainline Kernel releases:

- 5.4.29 or later
- 5.5.14 or later
- 5.6.0 or later

