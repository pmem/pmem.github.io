---
title: pmem Repositories
onecol: true
---

#### pmem Repositories

Here is a list of all the repositories in the *pmem*
organization in GitHub under
[https://github.com/pmem](https://github.com/pmem).

{::nomarkdown}
<fieldset class="repo">
	<legend>
		PMDK Repositories
	</legend>
	<p>
	The <a href="//pmem.io/pmdk/">Persistent Memory Development Kit</a> is a collection
	of libraries and tools.  The source is spread across
	many repositories.  Components are separated like this to
	help with the logistics of parallel development and
	asynchronous delivery.
	<br><br>
	<strong>Components Focused on Persistence:</strong>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmdk">pmdk</a></td>
			<td>PMDK Core C libraries and tools:<br>
				<ul>
					<li>libpmem
					<li>libpmem2 (in development)
					<li>libpmemobj
					<li>libpmemblk
					<li>libpmemlog
					<li>librpmem
					<li>libpmempool
					<li>pmempool utility
					<li>Core PMDK C examples
					<li>Web content for pmem.io/pmdk (in <strong>gh-pages</strong> branch)
				</ul>
			</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/libpmemobj-cpp">libpmemobj-cpp</a></td>
			<td>C++ bindings &amp; containers for libpmemobj</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/libpmemobj-js">libpmemobj-js</a></td>
			<td>JavaScript bindings for libpmemobj</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv">pmemkv</a></td>
			<td>Transactional Key-Value Store: Top-Level C API</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-tools">pmemkv-tools</a></td>
			<td>Benchmarks and tools for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-python">pmemkv-python</a></td>
			<td>Python bindings for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-nodejs">pmemkv-nodejs</a></td>
			<td>NodeJS bindings for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-ruby">pmemkv-ruby</a></td>
			<td>Ruby bindings for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-java">pmemkv-java</a></td>
			<td>Java bindings for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemkv-jni">pmemkv-jni</a></td>
			<td>Java bindings via JNI for pmemkv</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmdk-convert">pmdk-convert</a></td>
			<td>Conversion tool for PMDK pools</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/valgrind">valgrind</a></td>
			<td>Enhanced valgrind containg the <strong>pmemcheck</strong> plugin</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmdk-tests">pmdk-tests</a></td>
			<td>Extended PMDK tests</td>
		</tr>
	</table>
	<br><br>
	<strong>Components Focused on Volatile Usages of pmem:</strong>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/memkind/memkind">memkind</a></td>
			<td>General-purpose malloc/free-style library<br>
			(Actually lives outside the <i>pmem</i> GitHub org<br>
			since it has a life outside of pmem as well)</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/vmemcache">vmemcache</a></td>
			<td>A buffer based LRU cache</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/vmem">vmem</a></td>
			<td>libvmem, the predecessor to <strong>libmemkind</strong>.<br>
			Maintenance-only -- use <strong>libmemkind</strong> for all new development.
		</tr>
	</table>
	<br><br>
	<strong>Experimental PMDK components (not yet ready for production use):</strong>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pcj">pcj</a></td>
			<td>Persistent Collections for Java</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/llpl">llpl</a></td>
			<td>Low-Level Persistence Library for Java</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmemfile">pmemfile</a></td>
			<td>Userspace implementation of file APIs using pmem</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/syscall_intercept">syscall_intercept</a></td>
			<td>Syscall intercepting library used by libpmemfile</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/vltrace">vltrace</a></td>
			<td>Tool for tracing syscalls</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pynvm">pynvm</a></td>
			<td>Experimental prototype Python bindings for libpmemobj</td>
		</tr>
	</table>
	<br><br>
	<strong>Other:</strong>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/dev-utils-kit">dev-utils-kit</a></td>
			<td>Tools used for development of projects under pmem organization (WIP)</td>
		</tr>
	</table>
</fieldset>
<fieldset class="repo">
	<legend>
		ndctl
	</legend>
	<p>
	<a href="http://pmem.io/ndctl">ndctl</a> is the Linux utility for managing persistent memory.
	<p>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/ndctl">ndctl</a></td>
			<td>ndctl, daxctl, and related libraries</td>
		</tr>
	</table>
</fieldset>
<fieldset class="repo">
	<legend>
		Web Content
	</legend>
	<p>
	The <a href="http://pmem.io">pmem.io website</a> is implemented as static content
	on GitHub using Jekyll, GitHub-flavored MarkDown, and
	some tool-generated HTML here and there.  Some sub-areas
	of the website live in the <strong>gh-pages</strong>
	branch of the corresponding repo (for example, pmdk and
	ndctl).
	<p>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/docs">docs</a></td>
			<td>Persistent Memory <a href="https://docs.pmem.io">Docbook</a></td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmem.github.io">pmem.github.io</a></td>
			<td>repo containing the pmem.io website (including blogs)</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmdk-examples">pmdk-examples</a></td>
			<td>PMDK examples and tutorials</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/book">book</a></td>
			<td>Examples used in the <a href="http://pmem.io/book/">pmem Programming Book</a></td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/knowledge-base">knowledge-base</a></td>
			<td>Knowledge Base for pmem.io</td>
		</tr>
	</table>
</fieldset>
<fieldset class="repo">
	<legend>
		pmem-aware Software
	</legend>
	<p>
	These repos contain <strong>experimental</strong> versions
	of software modified to leverage persistent memory.  Typically,
	when the features are mature and tested they become part of
	the upstream repo.
	<p>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pelikan">pelikan</a></td>
			<td>Working tree for development of pmem-related features for Twitter's Pelikan</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmem-rocksdb">pmem-rocksdb</a></td>
			<td>RocksDB modified to use pmem</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmem-redis">pmem-redis</a></td>
			<td>Redis, enhanced to use pmem</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/pmse">pmse</a></td>
			<td>MongoDB pmem Storage Engine Prototype</td>
		</tr>
	</table>
</fieldset>
<fieldset class="repo">
	<legend>
		Inactive
	</legend>
	<p>
	These repos are no longer under active development or
	use.  We archive them here for reference.
	<p>
	<table>
		<tr>
			<th>Repo Name</td>
			<th>Description</th>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/linux-examples">linux-examples</a></td>
			<td>original ideas</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/redis">redis</a></td>
			<td>Initial pmem enhancements to Redis</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/rocksdb">rocksdb</a></td>
			<td>Initial pmem enhancements to RocksDB</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/libcxx">libcxx</a></td>
			<td>Experimental pmem-aware libcxx</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/mpi-pmem-ext">mpi-pmem-ext</a></td>
			<td>MPI Extensions for pmem</td>
		</tr>
		<tr>
			<td><a href="https://github.com/pmem/issues">issues</a></td>
			<td>Archive of some old issues.  No longer in-use.</td>
		</tr>
	</table>
</fieldset>
{:/}
