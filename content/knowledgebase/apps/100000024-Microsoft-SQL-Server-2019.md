---
title: Microsoft SQL Server 2019
description: ''
layout: doc
categories: [apps]
tags: [persistent memory, pmem, microsoft, sql, sql server, 2019, imdb, in-memory database, database]
author: Steve Scargall
docid: 100000024
creation_date: 2020-03-24
modified_date:
---

This document provides links to information and resources for Microsoft SQL Server 2019 (15.x) or later for Windows and Linux.

- Home: [https://www.microsoft.com/en-us/sql-server/sql-server-2019](https://www.microsoft.com/en-us/sql-server/sql-server-2019)
- Download: [https://www.microsoft.com/en-us/sql-server/sql-server-downloads](https://www.microsoft.com/en-us/sql-server/sql-server-downloads)

## What's New

SQL Server [In-Memory Database](https://docs.microsoft.com/en-us/sql/relational-databases/in-memory-database?view=sql-server-ver15) technologies leverage modern hardware innovation to deliver unparalleled performance and scale. SQL Server 2019 (15.x) builds on earlier innovations in this area, such as in-memory online transaction processing (OLTP), to unlock a new level of scalability across all your database workloads.

| **New feature or update**                    | Details                                                      |
| -------------------------------------------- | ------------------------------------------------------------ |
| Hybrid buffer pool                           | New feature of the SQL Server Database Engine where database pages sitting on database files placed on a persistent memory (PMEM) device will be directly accessed when required. See [Hybrid buffer pool](https://docs.microsoft.com/en-us/sql/database-engine/configure-windows/hybrid-buffer-pool?view=sql-server-ver15). |
| Memory-optimized TempDB metadata             | SQL Server 2019 (15.x) introduces a new feature that is part of the [In-Memory Database](https://docs.microsoft.com/en-us/sql/relational-databases/in-memory-database?view=sql-server-ver15) feature family, memory-optimized TempDB metadata, which effectively removes this bottleneck and unlocks a new level of scalability for TempDB heavy workloads. In SQL Server 2019 (15.x), the system tables involved in managing temporary table metadata can be moved into latch-free non-durable memory-optimized tables. See [Memory-Optimized TempDB Metadata](https://docs.microsoft.com/en-us/sql/relational-databases/databases/tempdb-database?view=sql-server-ver15#memory-optimized-tempdb-metadata). |
| In-Memory OLTP support for Database Snapshot | SQL Server 2019 (15.x) introduces support for creating [Database Snapshots](https://docs.microsoft.com/en-us/sql/relational-databases/databases/database-snapshots-sql-server?view=sql-server-ver15) of databases that include memory-optimized filegroups. |



## Documentation

- [What's new in SQL Server 2019 (15.x)](https://docs.microsoft.com/en-us/sql/sql-server/what-s-new-in-sql-server-ver15?view=sql-server-ver15)
- [How to deploy persistent memory in Windows Server using Storage Spaces Direct](https://docs.microsoft.com/en-us/windows-server/storage/storage-spaces/deploy-pmem)
- [How to configure persistent memory (PMEM) for SQL Server on Linux](https://docs.microsoft.com/en-us/sql/linux/sql-server-linux-configure-pmem?view=sql-server-ver15)
- [Understand the Hybrid Buffer Pool](https://docs.microsoft.com/en-us/sql/database-engine/configure-windows/hybrid-buffer-pool?view=sql-server-ver15)
- [How to add a persisted log buffer to a database](https://docs.microsoft.com/en-us/sql/relational-databases/databases/add-persisted-log-buffer?view=sql-server-ver15)



## Learning & Training

- [Microsoft SQL Server 2019: New Features](https://www.lynda.com/SQL-Server-tutorials/Improvements-persistent-memory/5010660/2219547-4.html). See Section 1.1 "Improvements to persistent memory" [Lynda]
- [Microsoft SQL Server 2019: New Features](https://www.linkedin.com/learning/microsoft-sql-server-2019-new-features/improvements-to-persistent-memory). See Section 1.1 "Improvements to persistent memory" [LinkedIn Learning]
- [Windows Server 2019 with Intel Optane DC persistent memory - Microsoft Ignite 2018](https://www.youtube.com/watch?v=8WMXkMLJORc) [YouTube]
- [How To: Creating a Persistent Log Buffer in SQL 2019 - Data Exposed](https://www.youtube.com/watch?v=g-beqlkmDvw) [YouTube]



## Books

- [SQL Server 2019 Revealed: Including Big Data Clusters and Machine Learning](https://www.amazon.com/SQL-Server-2019-Revealed-Including/dp/148425418X)
- [SQL Server 2019 Administration Inside Out](https://www.amazon.com/SQL-Server-2019-Administration-Inside-ebook/dp/B085P1HSC2)
- [Pro SQL Server 2019 Administration: A Guide for the Modern DBA](https://www.amazon.com/Pro-SQL-Server-2019-Administration-ebook/dp/B07ZC1XC9Z)
- [Microsoft SQL Server 2019: A Beginner's Guide](https://www.amazon.com/Microsoft-SQL-Server-2019-Beginners-ebook/dp/B082K92PL7)
