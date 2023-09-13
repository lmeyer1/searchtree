# Search tree

Specialized radix tree implementation for indexing articles for full text search.

The trees are stored in binary files. The main tree is a radix tree and the document tree is a binary trie.


Properties of the data
======================
The encoded strings contain `[ !"0-9a-z]`. ` !"` are word separators encoding the distance of the words
(` `: adjacent, `!` separated by one word, `"` separated by two words. With this caracter set in ASCII, the first
bit is always 0, the second and third bit are either 00 or 11. We thus use only 6 bits to encode each caracter of
the words.

There are about 24418 indexed documents with ids ranging from 1 to 91481. This requires i16 and i32 respectively to store.
These documents contain about 181548 distinct words and 1838738 words in total in 30367 documents. There are between 4 and 313 distinct words in a document. Only 15 documents have more than 255 words. Words occur bewteen 1 and 21771 times.

Word tree
=========

The file is built out of chunks of 20B. Each chunk either represents a node of the radix tree or an element in a
linked list containing the documents linked to a node.

Binary structure
----------------

Each node chunk is as follows:
- a 32 bit header
    - 1 bit has_node_0
    - 1 bit has_node_1
    - 1 bit is_leaf
    - 5 bits str_len length of the node's string in bits
    - 3 bytes the node's string (can be continued in the three following fields)

- the pointer to the node 0 or 32 bits of the node's string (if has_node_0 == 0)
- the pointer to the node 1 or 32 bits of the node's string (if has_node_1 == 0)
- the pointer to the document list or 32 bits of the node's string (if is_leaf == 0)
- the number of distinct document in this and the children's document lists

| 32bit header | i32 ptr_node_0 | i32 ptr_node_1 | i32 ptr_list | i32 df |

Each pointer contains the offset in chunks. It must be multiplied by 20 to get the byte offset of the memory address.
The first node is at offset 1 and has an empty string and an empty ptr_list.

The first chunk is a special header. It contains the pointer to the last used chunk (used to place a new chunk),
and a pointer to the first freed chunk (chunks may get freed when string occurrences are deleted). A freed chunk
only contains a pointer to the next freed chunk. When inserting new string occurrences, freed chunks are filled
before the extending after the last used chunk.

Document list:
- The first chunk starts with i16 count links and i16 reserved
- The last byte of a chunk is the link to the next chunk or 0
- The 3 or 4 i32 remaining consist of
    - 8 bits a bitmask encoding the fields in the document where the word occurs
    - 24 bits the document id

Documents tree
==============

The documents tree is a binary search tree, with the nodes arranged as follows:

```
Root: 1 ----- 10 ----- 100
        \        \
         \        ---- 101
          \
           -- 11 ----- 110
```

Each node consist of 10B as follows

- i24 pointer to node 0
- i24 pointer to node 1
- i32 pointer to word list or nul if the node is not a leaf

The word list is a linked list as above. The first two items are two i16 representing the total number of words
and the number of distinct words. The words are represented by the i32 pointers to their nodes in the word tree.
The number of words per chunk must be determined from statistics of the data, such that the space requirement is minimal.

Top level file structure
========================
The index file contains three blocks.
The first two blocks contain the two trees.
The docs list of the words tree and the words list of the docs tree are similar instructure and can be interleaved
in one block.
The file header just consists of two i32 representing the offsets of the start of the second and third block.

Expected sizes
==============

Word tree
181548 words, 2n = 363096 nodes, x20B = 6.93MB
Doc tree
24418 docs (k), max id 91481 (n), min k = 24418, max (1 + log2(n)-log2(k))\*k = 70947 nodes, 693 kB
as list: 91481 items, i32 (4 B each), 357 kB
Word list 1.84 M reads
4 pointers per chunk 8.91 MB, 402 k extra reads
8	11.3 MB, 189 k extra reads
16	16.5 MB, 88.0 k extra reads
Docs list
4 pointers per chunk 7.30 MB, 448 k extra reads
8	7.54 MB, 216 k extra reads
16	7.95 MB, 100 k extra reads