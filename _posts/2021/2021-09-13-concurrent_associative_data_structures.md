---
title: Concurrent associative data structures in libpmemobj-cpp
author: svinogra
layout: post
identifier: concurrent_associative_data_structures
---

## Introduction
This blog post describes design approaches of two concurrent data structures available in the libpmemobj-cpp library: ```pmem::obj::concurrent_hash_map``` and ```pmem::obj::concurrent_map```. Both are associative data structures composed of a collection of key and value pairs, such that each possible key appears at most once in the collection. The main difference between them is that the concurrent hash map is unordered, while the concurrent map is ordered by keys. The ```pmem::obj::concurrent_map``` and ```pmem::obj::concurrent_hash_map``` structures were inspired by the [oneAPI Threading Building Blocks (oneTBB)](https://github.com/oneapi-src/oneTBB) library which provides implementations of these concurrent data structures designed for volatile memory. The ```concurrent``` term means that data strcutres are intended for use in parallel environment when multiple threads can concurrently call methods of a data structure without additional synchronization required. These data structures designed to reside on persistentn memory and uses design consideration described in the [previous blog post](https://pmem.io/2021/07/28/concurrency.html).<br/>
We describe each data structure with a focus on three main methods of associative containers: ```find```, ```insert```, and ```erase```.

## Concurrent map
The  ```pmem::obj::concurrent_map``` implementation is based on a concurrent skip list data structure. The [oneTBB](https://github.com/oneapi-src/oneTBB) library supplies ```tbb::concurrent_map```, which is designed for volatile memory. It is built on top of the lock-free concurrent skip list data structure. But in ```libpmemobj-cpp``` library we chose a provably correct [scalable concurrent skip list implementation with fine-grain locking](https://www.cs.tau.ac.il/~shanir/nir-pubs-web/Papers/OPODIS2006-BA.pdf) distinguished by a combination of simplicity and scalability which is easier to port for persistent memory use case. Figure 1 demonstrates the basic idea of the skip list data structure. It is a multilayered linked list-like data structure where the bottom layer is an ordered linked list. Each higher layer acts as an “express lane” for the following lists and allows it to skip elements during lookup operations. An element in layer `i` appears in layer `i+1` with some fixed probability `p` (in our implementation `p = 1/2`). That is, the frequency of nodes of a particular height decreases exponentially with the height. Such properties allow it to achieve `O(log n)` average time complexity for lookup, insert, and delete operations.
<figure class="image">
  <img src="/assets/concurrent_associative_containers_fig1.png" alt="Skip List Overview">
  <figcaption>Figure 1. Finding key=9 in the skip list data structure.</figcaption>
</figure>

### Insert operation ###
The insert operation, shown in Figure 2, employs fine-grained locking schema for thread-safety and consists of the following basic steps to insert a new node with `key=7` into the list:
1. Allocate the new node with randomly generated height.
2. Find a position to insert the new node. We must find the predecessor and successor nodes on each level.
3. Acquire locks for each predecessor node.
4. Check that the successor nodes have not been changed. If validation fails, the thread encountered a conflicting insert operation, so it releases the locks it
acquired and retries from step 2.
5. Insert the new node to all layers starting from the bottom one. Since the find operation is lock-free, we must update pointers on each level atomically using store-with-release memory semantics.

<figure class="image">
  <img src="/assets/concurrent_associative_containers_fig2.png" alt="Skip List Insert">
  <figcaption>Figure 2. Inserting a new node with key=7 into the concurrent skip list.</figcaption>
</figure>

The algorithm described earlier is thread-safe, but it is not enough to be fault tolerant on persistent memory. There is a possible persistent memory leak if a program unexpectedly terminates between the first and fifth steps of our algorithm. Furthermore, we cannot wrap the above mentioned steps in single transaction because it does not support isolation out-of-the-box and step 1 executed before we acquired any locks. To make insert operation fault-taulerant we used technique to publish allocations in linked list based data structures described in [previous blog post](https://pmem.io/2021/07/28/concurrency.html). Our implemenattion of the concurrent skip list employes `enumerable_thread_specific` to track `insert` operations. On a process restart the `runtime_initialize()` method should be called to process unfinished insert operations interrupted by abnormal process termination.<br/>
Figure 3 illustrates the approach of this fault-tolerant insert algorithm. When a thread allocates a new node, the pointer to that node is kept in persistent thread-local storage, and the node is reachable through this persistent thread-local storage. Then the algorithm inserts the new node to the skip list by linking it to all layers using the thread-safe algorithm described earlier. Finally, the pointer in the persistent thread-local storage is removed because the new node is reachable now via skip list itself.
<figure class="image">
  <img src="/assets/concurrent_associative_containers_fig3.png" alt="Skip List Insert">
  <figcaption>Figure 3. Fault-tolerant insert in the concurrent skip list.</figcaption>
</figure>

### Find operation ###
Because the find operation is non-modifying, it does not have to deal with data consistency issues. The lookup operation for the target element always begins from the topmost layer. The algorithm proceeds horizontally until the next element is greater or equal to the target. Then it drops down vertically to the next lower list if it cannot proceed on the current level. Figure 1 illustrates how the find operation works for the element with key=9. The search starts from the highest level and immediately goes from dummy head node to the node with key=4, skipping nodes with keys 1, 2, 3. On the node with key=4, the search is dropped two layers down and goes to the node with key=8. Then it drops one more layer down and proceeds to the desired node with key=9.<br/>
The find operation is wait-free. That is, every find operation is bound only by the number of steps the algorithm takes. And a thread is guaranteed to complete
the operation regardless of the activity of other threads. The implementation of `pmem::obj::concurrent_map` uses atomic load-with-acquire memory semantics when reading pointers to the next node.

### Erase operation ###
The implementation of the erase operation for `pmem::obj::concurrent_map` is not thread-safe. This method cannot be called concurrently with other methods of the
concurrent ordered map because there is a memory reclamation problem that is non-trivial to solve in C++ without a garbage collector. There is a way to logically extract a node from a skip list in a thread-safe manner, but it is not trivial to detect when it is safe to delete the removed node because other threads may still have access to the node. There are possible solutions, such as hazard pointers or epoch-based memory reclamation, but these can impact the performance of the find and insert operations.

## Concurrent hash map
